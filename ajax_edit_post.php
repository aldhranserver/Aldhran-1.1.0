<?php
/**
 * AJAX EDIT POST - Finaler Fix mit korrektem Dateinamen (db.php)
 */

// Wir nutzen den absoluten Pfad zur db.php im includes-Ordner
$baseDir = dirname(__FILE__);
$dbPath = $baseDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php';

session_start();

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $myId    = (int)($_SESSION['user_id'] ?? 0);
    $myPriv  = (int)($_SESSION['priv_level'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($post_id <= 0 || empty($content)) {
        echo "error_input";
        exit;
    }

    // Berechtigung prüfen (wir nutzen $conn aus der db.php)
    $stmt = $conn->prepare("SELECT author_id FROM spike_posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        if ($res['author_id'] == $myId || $myPriv >= 3) {
            $update = $conn->prepare("UPDATE spike_posts SET content = ? WHERE id = ?");
            $update->bind_param("si", $content, $post_id);
            
            if ($update->execute()) {
                echo "success";
            } else {
                echo "error_db";
            }
        } else {
            echo "error_unauthorized";
        }
    } else {
        echo "error_not_found";
    }
} else {
    echo "error_method";
}
exit;