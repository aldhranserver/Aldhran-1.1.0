<?php
/**
 * VIEWBOARD LOGIC - Spike Forum
 * Version: 2.0.0 - SECURITY: PDO Migration & Atomic Batch Mod
 */
require_once(__DIR__ . '/../includes/spike_bb_helper.php'); 

if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$myPriv     = (int)($_SESSION['priv_level'] ?? 0);
$myId        = (int)($_SESSION['user_id'] ?? 0);
$myStanding = (int)($_SESSION['standing'] ?? 0);
$board_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Board-Details laden via PDO
$stmt_board = $db->prepare("
    SELECT b.*, c.min_priv as cat_min_view, c.min_priv_post as cat_min_post 
    FROM spike_boards b
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE b.id = ?
");
$stmt_board->execute([$board_id]);
$board_info = $stmt_board->fetch();

if (!$board_info) { 
    header("Location: index.php?p=spike&err=not_found"); 
    exit; 
}

// Berechtigungen berechnen
$effective_min_view = ($board_info['min_priv'] > 0) ? (int)$board_info['min_priv'] : (int)$board_info['cat_min_view'];
$effective_min_post = ($board_info['min_priv_post'] > 0) ? (int)$board_info['min_priv_post'] : (int)$board_info['cat_min_post'];

if ($myPriv < 4 && $myPriv < $effective_min_view) {
    header("Location: index.php?p=spike&err=no_access");
    exit;
}

// 2. Daten für das Verschiebe-Dropdown
$stmt_all_b = $db->prepare("SELECT id, title FROM spike_boards WHERE id != ? ORDER BY title ASC");
$stmt_all_b->execute([$board_id]);
$all_boards = $stmt_all_b->fetchAll();

// --- 3. BATCH MODERATION HANDLER (PDO ATOMIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_mod_action'], $_POST['selected_threads']) && $myPriv >= 4) {
    // CSRF Schutz für Admin-Aktionen
    checkToken($_POST['csrf_token'] ?? '');

    $thread_ids = array_map('intval', $_POST['selected_threads']);
    if (!empty($thread_ids)) {
        // Platzhalter für IN-Statement generieren (?,?,?)
        $placeholders = implode(',', array_fill(0, count($thread_ids), '?'));
        $action = $_POST['mod_batch_action'] ?? '';

        try {
            $db->beginTransaction();

            if ($action === 'move' && (int)$_POST['target_board'] > 0) {
                $target_board = (int)$_POST['target_board'];
                $stmt = $db->prepare("UPDATE spike_threads SET board_id = ? WHERE id IN ($placeholders)");
                $stmt->execute(array_merge([$target_board], $thread_ids));
                aldhran_log("BATCH_MOD", "Moved threads to board #$target_board", $myId);
            } 
            elseif ($action === 'delete') {
                // Erst Posts, dann Threads
                $stmt_p = $db->prepare("DELETE FROM spike_posts WHERE thread_id IN ($placeholders)");
                $stmt_p->execute($thread_ids);
                $stmt_t = $db->prepare("DELETE FROM spike_threads WHERE id IN ($placeholders)");
                $stmt_t->execute($thread_ids);
                aldhran_log("BATCH_MOD", "Deleted multiple threads", $myId);
            } 
            elseif ($action === 'toggle_lock') {
                $stmt = $db->prepare("UPDATE spike_threads SET is_locked = 1 - is_locked WHERE id IN ($placeholders)");
                $stmt->execute($thread_ids);
                aldhran_log("BATCH_MOD", "Toggled lock for multiple threads", $myId);
            } 
            elseif ($action === 'toggle_sticky') {
                $stmt = $db->prepare("UPDATE spike_threads SET is_sticky = 1 - is_sticky WHERE id IN ($placeholders)");
                $stmt->execute($thread_ids);
                aldhran_log("BATCH_MOD", "Toggled sticky for multiple threads", $myId);
            }

            $db->commit();
            header("Location: index.php?p=viewboard&id=$board_id&msg=mod_success");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Batch Mod Error: " . $e->getMessage());
        }
    }
}

// 4. Threads laden via PDO
$stmt_threads = $db->prepare("
    SELECT t.*, u.username, u.user_title,
    (SELECT COUNT(id) FROM spike_posts WHERE thread_id = t.id) as reply_count,
    (SELECT created_at FROM spike_posts WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) as last_post_date
    FROM spike_threads t
    JOIN users u ON t.author_id = u.id
    WHERE t.board_id = ?
    HAVING reply_count > 0
    ORDER BY t.is_sticky DESC, last_post_date DESC
");
$stmt_threads->execute([$board_id]);
$threads = $stmt_threads->fetchAll();

// --- 5. NEUES THEMA ERSTELLEN LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thread'])) {
    checkToken($_POST['csrf_token'] ?? '');
    
    $can_post_here = ($myId > 0 && $myStanding < 3 && $myPriv >= $effective_min_post);

    if ($can_post_here) {
        $title = trim($_POST['thread_title'] ?? '');
        $content = trim($_POST['thread_content'] ?? '');
        
        if (!empty($title) && !empty($content)) {
            try {
                $db->beginTransaction();

                $stmt_t = $db->prepare("INSERT INTO spike_threads (board_id, author_id, title) VALUES (?, ?, ?)");
                $stmt_t->execute([$board_id, $myId, $title]);
                $new_thread_id = $db->lastInsertId();

                $stmt_p = $db->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
                $stmt_p->execute([$new_thread_id, $myId, $content]);
                
                $stmt_u = $db->prepare("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = ?");
                $stmt_u->execute([$myId]);

                $db->commit();
                header("Location: index.php?p=viewthread&id=$new_thread_id&msg=thread_created");
                exit;
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
    }
}