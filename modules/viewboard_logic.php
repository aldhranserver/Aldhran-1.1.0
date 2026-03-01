<?php
/**
 * VIEWBOARD LOGIC - Spike Forum
 * Version: 0.8.0 - ADDED: Batch Mod Actions & Ghost Filter
 */
require_once(__DIR__ . '/../includes/spike_bb_helper.php'); 

if (!defined('IN_CMS')) { exit; }

$myPriv     = (int)($_SESSION['priv_level'] ?? 0);
$myId        = (int)($_SESSION['user_id'] ?? 0);
$myStanding = (int)($_SESSION['standing'] ?? 0);
$board_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Board-Details laden
$board_stmt = $conn->prepare("
    SELECT b.*, c.min_priv as cat_min_view, c.min_priv_post as cat_min_post 
    FROM spike_boards b
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE b.id = ?
");
$board_stmt->bind_param("i", $board_id);
$board_stmt->execute();
$board_info = $board_stmt->get_result()->fetch_assoc();

if (!$board_info) { 
    header("Location: index.php?p=spike&err=not_found"); 
    exit; 
}

$effective_min_view = ($board_info['min_priv'] > 0) ? (int)$board_info['min_priv'] : (int)$board_info['cat_min_view'];
$effective_min_post = ($board_info['min_priv_post'] > 0) ? (int)$board_info['min_priv_post'] : (int)$board_info['cat_min_post'];

if ($myPriv < 4 && $myPriv < $effective_min_view) {
    header("Location: index.php?p=spike&err=no_access");
    exit;
}

// 2. Daten für das Verschiebe-Dropdown
$all_boards_res = $conn->query("SELECT id, title FROM spike_boards WHERE id != $board_id ORDER BY title ASC");

// --- 3. BATCH MODERATION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_mod_action']) && isset($_POST['selected_threads']) && $myPriv >= 4) {
    $thread_ids = array_map('intval', $_POST['selected_threads']);
    $ids_string = implode(',', $thread_ids);
    $action = $_POST['mod_batch_action'] ?? '';

    if ($action === 'move' && (int)$_POST['target_board'] > 0) {
        $target_board = (int)$_POST['target_board'];
        $conn->query("UPDATE spike_threads SET board_id = $target_board WHERE id IN ($ids_string)");
    } 
    elseif ($action === 'delete') {
        $conn->query("DELETE FROM spike_posts WHERE thread_id IN ($ids_string)");
        $conn->query("DELETE FROM spike_threads WHERE id IN ($ids_string)");
    } 
    elseif ($action === 'toggle_lock') {
        $conn->query("UPDATE spike_threads SET is_locked = 1 - is_locked WHERE id IN ($ids_string)");
    } 
    elseif ($action === 'toggle_sticky') {
        $conn->query("UPDATE spike_threads SET is_sticky = 1 - is_sticky WHERE id IN ($ids_string)");
    }

    header("Location: index.php?p=viewboard&id=$board_id&msg=mod_success");
    exit;
}

// 4. Threads laden (Inklusive Ghost-Filter)
$threads_res = $conn->query("
    SELECT t.*, u.username, u.user_title,
    (SELECT COUNT(id) FROM spike_posts WHERE thread_id = t.id) as reply_count,
    (SELECT created_at FROM spike_posts WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) as last_post_date
    FROM spike_threads t
    JOIN users u ON t.author_id = u.id
    WHERE t.board_id = $board_id
    HAVING reply_count > 0
    ORDER BY t.is_sticky DESC, last_post_date DESC
");

// --- 5. NEUES THEMA ERSTELLEN LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thread'])) {
    $can_post_here = ($myId > 0 && $myStanding < 3 && $myPriv >= $effective_min_post);

    if ($can_post_here) {
        $title = trim($_POST['thread_title'] ?? '');
        $content = trim($_POST['thread_content'] ?? '');
        
        if (!empty($title) && !empty($content)) {
            $ins_t = $conn->prepare("INSERT INTO spike_threads (board_id, author_id, title) VALUES (?, ?, ?)");
            $ins_t->bind_param("iis", $board_id, $myId, $title);
            
            if ($ins_t->execute()) {
                $new_thread_id = $conn->insert_id;
                $ins_p = $conn->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
                $ins_p->bind_param("iis", $new_thread_id, $myId, $content);
                $ins_p->execute();
                
                $conn->query("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = $myId");
                header("Location: index.php?p=viewthread&id=$new_thread_id&msg=thread_created");
                exit;
            }
        }
    }
}