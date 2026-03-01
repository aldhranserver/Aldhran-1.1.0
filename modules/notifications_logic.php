<?php
/**
 * NOTIFICATIONS LOGIC - Spike Forum
 * Version: 1.1 - Mark all as read & History
 */
if (!defined('IN_CMS')) { exit; }

$myId = (int)($_SESSION['user_id'] ?? 0);

// --- ACTION: ALL READ ---
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE spike_notifications SET is_read = 1 WHERE user_id = $myId");
    header("Location: index.php?p=notifications&msg=cleared");
    exit;
}

// --- ACTION: DELETE SINGLE ---
if (isset($_GET['delete_notif'])) {
    $nid = (int)$_GET['delete_notif'];
    $conn->query("DELETE FROM spike_notifications WHERE id = $nid AND user_id = $myId");
    header("Location: index.php?p=notifications");
    exit;
}

// --- FETCH NOTIFICATIONS ---
$notif_res = $conn->query("
    SELECT n.*, u.username, t.title as thread_title 
    FROM spike_notifications n
    JOIN users u ON n.source_user_id = u.id
    JOIN spike_threads t ON n.thread_id = t.id
    WHERE n.user_id = $myId
    ORDER BY n.is_read ASC, n.created_at DESC 
    LIMIT 30
");
?>