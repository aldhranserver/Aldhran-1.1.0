<?php
/**
 * VIEWTHREAD LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Anti-Spam Hardening
 */
require_once(__DIR__ . '/../includes/spike_bb_helper.php'); 

if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$myPriv     = (int)($_SESSION['priv_level'] ?? 0);
$myId       = (int)($_SESSION['user_id'] ?? 0);
$myStanding = (int)($_SESSION['standing'] ?? 0);
$thread_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- 1. SETTINGS LADEN (PDO) ---
$spam_res = $db->query("SELECT setting_key, setting_value FROM spike_settings")->fetchAll();
$spam_cfg = [];
foreach($spam_res as $s) { $spam_cfg[$s['setting_key']] = $s['setting_value']; }

$cooldown_limit = (int)($spam_cfg['spam_cooldown'] ?? 30);
$min_bs_links   = (int)($spam_cfg['spam_min_bs_links'] ?? 1);

// --- 2. THREAD & BOARD DATEN LADEN ---
$stmt_t = $db->prepare("
    SELECT t.*, b.title as board_title, b.id as board_id, 
           b.min_priv as board_min_view, b.min_priv_post as board_min_post,
           c.min_priv as cat_min_view, c.min_priv_post as cat_min_post 
    FROM spike_threads t
    JOIN spike_boards b ON t.board_id = b.id
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE t.id = ?
");
$stmt_t->execute([$thread_id]);
$thread = $stmt_t->fetch();

if (!$thread) { 
    header("Location: index.php?p=spike&err=not_found"); 
    exit; 
}

// --- PRIORITÄTS-BERECHNUNG ---
$effective_min_view = ($thread['board_min_view'] > 0) ? (int)$thread['board_min_view'] : (int)$thread['cat_min_view'];
$effective_min_post = ($thread['board_min_post'] > 0) ? (int)$thread['board_min_post'] : (int)$thread['cat_min_post'];

// Sichtbarkeits-Check (Admins BS 4+ kommen immer rein)
if ($myPriv < 4 && $myPriv < $effective_min_view) {
    header("Location: index.php?p=viewboard&id=".$thread['board_id']."&err=no_access");
    exit;
}

// --- 3. BEITRÄGE LADEN ---
$stmt_p = $db->prepare("
    SELECT p.*, u.username, u.avatar_url, u.user_title, u.standing, u.forum_posts, u.forum_signature, u.priv_level
    FROM spike_posts p
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.thread_id = ?
    ORDER BY p.created_at ASC
");
$stmt_p->execute([$thread_id]);
$posts = $stmt_p->fetchAll();

// --- 4. REPLY LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    checkToken($_POST['csrf_token'] ?? '');
    
    $can_write = ($myId > 0 && $myStanding < 3 && !$thread['is_locked'] && $myPriv >= $effective_min_post);
    
    if ($can_write) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        
        // Cooldown Check via PDO
        $stmt_last = $db->prepare("SELECT created_at FROM spike_posts WHERE author_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt_last->execute([$myId]);
        $last_p = $stmt_last->fetch();

        if ($last_p) {
            $diff = time() - strtotime($last_p['created_at']);
            if ($diff < $cooldown_limit) {
                header("Location: index.php?p=viewthread&id=$thread_id&err=spam_cooldown&wait=".($cooldown_limit - $diff)); 
                exit;
            }
        }

        // Link Check (Spam-Schutz)
        if ($myPriv < $min_bs_links && preg_match('/(http|https|www)/i', $reply_content)) {
            header("Location: index.php?p=viewthread&id=$thread_id&err=no_links_allowed"); 
            exit;
        }

        if (!empty($reply_content)) {
            try {
                $db->beginTransaction();

                $ins_p = $db->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
                $ins_p->execute([$thread_id, $myId, $reply_content]);
                
                $upd_u = $db->prepare("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = ?");
                $upd_u->execute([$myId]);

                // Benachrichtigung an Thread-Ersteller
                if ($thread['author_id'] != $myId) {
                    $ins_n = $db->prepare("INSERT INTO spike_notifications (user_id, source_user_id, thread_id, type) VALUES (?, ?, ?, 'reply')");
                    $ins_n->execute([$thread['author_id'], $myId, $thread_id]);
                }

                $db->commit();
                header("Location: index.php?p=viewthread&id=$thread_id&msg=replied"); 
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Reply Error: " . $e->getMessage());
            }
        }
    }
}

// --- 5. MODERATION ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mod_action']) && $myPriv >= 3) {
    checkToken($_POST['csrf_token'] ?? '');
    $action = $_POST['mod_action'];

    // A) Gesamten Thread löschen (Level 4+)
    if ($action === 'delete_thread' && $myPriv >= 4) {
        $db->beginTransaction();
        $db->prepare("DELETE FROM spike_posts WHERE thread_id = ?")->execute([$thread_id]);
        $db->prepare("DELETE FROM spike_threads WHERE id = ?")->execute([$thread_id]);
        $db->commit();
        
        aldhran_log("MOD_DELETE", "Deleted entire thread #$thread_id", $myId);
        header("Location: index.php?p=viewboard&id=" . $thread['board_id'] . "&msg=thread_deleted");
        exit;
    }

    // B) Einzelnen Post löschen
    if ($action === 'delete_post') {
        $post_id = (int)($_POST['post_id'] ?? 0);
        $db->prepare("DELETE FROM spike_posts WHERE id = ?")->execute([$post_id]);
        
        // Auto-Delete Check
        $cnt = $db->prepare("SELECT COUNT(id) FROM spike_posts WHERE thread_id = ?");
        $cnt->execute([$thread_id]);
        
        if ($cnt->fetchColumn() == 0) {
            $db->prepare("DELETE FROM spike_threads WHERE id = ?")->execute([$thread_id]);
            header("Location: index.php?p=viewboard&id=" . $thread['board_id'] . "&msg=thread_auto_deleted");
        } else {
            header("Location: index.php?p=viewthread&id=$thread_id&msg=post_deleted");
        }
        exit;
    }
}