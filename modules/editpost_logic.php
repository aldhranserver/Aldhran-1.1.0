<?php
/**
 * EDITPOST LOGIC - Spike Forum
 * Location: /modules/editpost_logic.php
 */
if (!defined('IN_CMS')) { exit; }

// Wir holen die DB-Verbindung
global $conn;

// --- IDENTITÄTS-CHECK (Passend zur index.php) ---
$finalID = (int)($_SESSION['user_id'] ?? 0);
$finalPriv = (int)($_SESSION['priv_level'] ?? 0);

// --- LOGIK START ---
$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) { 
    header("Location: index.php?p=forum"); 
    exit; 
}

// Post laden
$sql = "SELECT p.*, t.`id` as thread_id 
        FROM spike_posts p 
        JOIN spike_threads t ON p.`thread_id` = t.`id` 
        WHERE p.`id` = $post_id";
$res = $conn->query($sql);
$post_data = $res->fetch_assoc();

if (!$post_data) { 
    exit("Post not found in Database."); 
}

// --- BERECHTIGUNG ---
// In deiner index.php ist Admin ab Level 4 oder 5. 
// Ich setze es hier auf >= 4, wie in deiner $can_edit Variable in der index.php.
$is_author = ($finalID > 0 && $finalID === (int)$post_data['author_id']);
$is_admin = ($finalPriv >= 4); 

if (!$is_author && !$is_admin) { 
    exit("Access denied. <br>Deine ID: $finalID <br>Author ID: " . $post_data['author_id'] . " <br>Dein Priv-Level: $finalPriv"); 
}

// Speichern
if (isset($_POST['save_edit'])) {
    $new_content = mysqli_real_escape_string($conn, $_POST['content']);
    
    $sql_update = "UPDATE spike_posts SET `content` = '$new_content', `updated_at` = NOW() WHERE `id` = $post_id";
    $conn->query($sql_update);
    
    header("Location: index.php?p=viewthread&id=" . (int)$post_data['thread_id']);
    exit;
}
?>