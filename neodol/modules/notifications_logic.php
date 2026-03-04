<?php
/**
 * NOTIFICATIONS LOGIC - Spike Forum
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$myId = (int)($_SESSION['user_id'] ?? 0);

if ($myId <= 0) {
    header("Location: index.php?p=login");
    exit;
}

// --- ACTION: ALL READ ---
if (isset($_POST['mark_all_read'])) {
    // Enterprise V2: CSRF Schutz
    checkToken($_POST['csrf_token'] ?? '');

    $stmt_read = $db->prepare("UPDATE spike_notifications SET is_read = 1 WHERE user_id = ?");
    if ($stmt_read->execute([$myId])) {
        header("Location: index.php?p=notifications&msg=cleared");
        exit;
    }
}

// --- ACTION: DELETE SINGLE ---
if (isset($_GET['delete_notif'])) {
    // Optional: Hier könnte man noch einen Token-Check via URL einbauen
    $nid = (int)$_GET['delete_notif'];
    
    $stmt_del = $db->prepare("DELETE FROM spike_notifications WHERE id = ? AND user_id = ?");
    if ($stmt_del->execute([$nid, $myId])) {
        header("Location: index.php?p=notifications");
        exit;
    }
}

// --- FETCH NOTIFICATIONS (PDO JOIN) ---
$stmt_fetch = $db->prepare("
    SELECT n.*, u.username, t.title as thread_title 
    FROM spike_notifications n
    JOIN users u ON n.source_user_id = u.id
    JOIN spike_threads t ON n.thread_id = t.id
    WHERE n.user_id = ?
    ORDER BY n.is_read ASC, n.created_at DESC 
    LIMIT 30
");
$stmt_fetch->execute([$myId]);
$notifications = $stmt_fetch->fetchAll();
?>