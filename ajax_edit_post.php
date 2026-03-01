<?php
/**
 * AJAX EDIT POST - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */

// Pfad-Logik für Standalone AJAX-Files
require_once(__DIR__ . '/includes/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wir erwarten reinen Text als Antwort für das Frontend
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $myId    = (int)($_SESSION['user_id'] ?? 0);
    $myPriv  = (int)($_SESSION['priv_level'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    // 1. Input Check
    if ($post_id <= 0 || empty($content)) {
        echo "error_input";
        exit;
    }

    // 2. Berechtigung prüfen via PDO
    $stmt = $db->prepare("SELECT author_id FROM spike_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post) {
        // Prüfen: Ist es der eigene Post oder ein Staff-Member?
        if ((int)$post['author_id'] === $myId || $myPriv >= 3) {
            
            try {
                $update = $db->prepare("UPDATE spike_posts SET content = ?, last_edit_at = NOW() WHERE id = ?");
                
                if ($update->execute([$content, $post_id])) {
                    // Erfolg: Audit Log schreiben
                    aldhran_log("POST_EDIT", "Post #$post_id edited", $myId, $post_id);
                    echo "success";
                } else {
                    echo "error_db";
                }
            } catch (Exception $e) {
                error_log("AJAX Edit Error: " . $e->getMessage());
                echo "error_db";
            }

        } else {
            // Unbefugter Änderungsversuch!
            aldhran_log("SECURITY_ALERT", "Unauthorized post edit attempt on #$post_id", $myId);
            echo "error_unauthorized";
        }
    } else {
        echo "error_not_found";
    }
} else {
    echo "error_method";
}
exit;