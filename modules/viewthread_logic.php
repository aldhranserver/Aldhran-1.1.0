<?php
/**
 * VIEWTHREAD LOGIC - Spike Forum
 * Version: 0.5.5 - FINAL: Board Priority Implementation
 */
require_once(__DIR__ . '/../includes/spike_bb_helper.php'); 

if (!defined('IN_CMS')) { exit; }

$myPriv     = (int)($_SESSION['priv_level'] ?? 0);
$myId       = (int)($_SESSION['user_id'] ?? 0);
$myStanding = (int)($_SESSION['standing'] ?? 0);
$thread_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- SETTINGS LADEN ---
$spam_res = $conn->query("SELECT setting_key, setting_value FROM spike_settings");
$spam_cfg = [];
while($s = $spam_res->fetch_assoc()) { $spam_cfg[$s['setting_key']] = $s['setting_value']; }
$cooldown_limit = (int)($spam_cfg['spam_cooldown'] ?? 30);
$min_bs_links   = (int)($spam_cfg['spam_min_bs_links'] ?? 1);

// 1. Thread & Board Daten laden (Hierarchie-Check Felder hinzugefügt)
$thread_stmt = $conn->prepare("
    SELECT t.*, b.title as board_title, b.id as board_id, 
           b.min_priv as board_min_view, b.min_priv_post as board_min_post,
           c.min_priv as cat_min_view, c.min_priv_post as cat_min_post 
    FROM spike_threads t
    JOIN spike_boards b ON t.board_id = b.id
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE t.id = ?
");
$thread_stmt->bind_param("i", $thread_id);
$thread_stmt->execute();
$thread = $thread_stmt->get_result()->fetch_assoc();

if (!$thread) { 
    header("Location: index.php?p=spike&err=not_found"); 
    exit; 
}

// --- PRIORITÄTS-BERECHNUNG ---
$effective_min_view = ($thread['board_min_view'] > 0) ? (int)$thread['board_min_view'] : (int)$thread['cat_min_view'];
$effective_min_post = ($thread['board_min_post'] > 0) ? (int)$thread['board_min_post'] : (int)$thread['cat_min_post'];

// Check: Darf der User den Thread sehen? (BS 4+ ist Gesetz)
if ($myPriv < 4 && $myPriv < $effective_min_view) {
    header("Location: index.php?p=viewboard&id=".$thread['board_id']."&err=no_access");
    exit;
}

// 2. Beiträge laden
$posts_stmt = $conn->prepare("
    SELECT p.*, u.username, u.avatar_url, u.user_title, u.standing, u.forum_posts, u.forum_signature, u.priv_level
    FROM spike_posts p
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.thread_id = ?
    ORDER BY p.created_at ASC
");
$posts_stmt->bind_param("i", $thread_id);
$posts_stmt->execute();
$posts_res = $posts_stmt->get_result();

// --- 3. REPLY LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    // Hier greift die effektive Board-Sperre
    $can_write = ($myId > 0 && $myStanding < 3 && !$thread['is_locked'] && $myPriv >= $effective_min_post);
    
    if ($can_write) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        
        // Cooldown Check
        $last_p = $conn->query("SELECT created_at FROM spike_posts WHERE author_id = $myId ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
        if ($last_p) {
            $diff = time() - strtotime($last_p['created_at']);
            if ($diff < $cooldown_limit) {
                header("Location: index.php?p=viewthread&id=$thread_id&err=spam_cooldown&wait=".($cooldown_limit - $diff)); 
                exit;
            }
        }

        // Link Check
        if ($myPriv < $min_bs_links && preg_match('/(http|https|www)/i', $reply_content)) {
            header("Location: index.php?p=viewthread&id=$thread_id&err=no_links_allowed"); 
            exit;
        }

        if (!empty($reply_content)) {
            $ins_post = $conn->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
            $ins_post->bind_param("iis", $thread_id, $myId, $reply_content);
            
            if ($ins_post->execute()) {
                $conn->query("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = $myId");
                if ($thread['author_id'] != $myId) {
                    $notif_stmt = $conn->prepare("INSERT INTO spike_notifications (user_id, source_user_id, thread_id, type) VALUES (?, ?, ?, 'reply')");
                    $notif_stmt->bind_param("iii", $thread['author_id'], $myId, $thread_id);
                    $notif_stmt->execute();
                }
                header("Location: index.php?p=viewthread&id=$thread_id&msg=replied"); 
                exit;
            }
        }
    } else {
        header("Location: index.php?p=viewthread&id=$thread_id&err=unauthorized_post"); 
        exit;
    }
}
// --- 4. MODERATION ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mod_action']) && $myPriv >= 3) {
    $action = $_POST['mod_action'];

    // A) DEN GESAMTEN THREAD LÖSCHEN (Nur Level 4+)
    if ($action === 'delete_thread' && $myPriv >= 4) {
        $conn->query("DELETE FROM spike_posts WHERE thread_id = $thread_id");
        $conn->query("DELETE FROM spike_threads WHERE id = $thread_id");
        header("Location: index.php?p=viewboard&id=" . $thread['board_id'] . "&msg=thread_deleted");
        exit;
    }

    // B) EINEN EINZELNEN POST LÖSCHEN
    if ($action === 'delete_post') {
        $post_id = (int)$_POST['post_id'];
        
        // 1. Post löschen
        $conn->query("DELETE FROM spike_posts WHERE id = $post_id");
        
        // 2. Prüfen: Ist der Thread jetzt leer?
        $check = $conn->query("SELECT COUNT(id) as cnt FROM spike_posts WHERE thread_id = $thread_id")->fetch_assoc();
        
        if ($check['cnt'] == 0) {
            // Wenn leer: Thread-Hülle ebenfalls löschen
            $conn->query("DELETE FROM spike_threads WHERE id = $thread_id");
            header("Location: index.php?p=viewboard&id=" . $thread['board_id'] . "&msg=thread_auto_deleted");
        } else {
            header("Location: index.php?p=viewthread&id=$thread_id&msg=post_deleted");
        }
        exit;
    }
}