<?php
/**
 * EDITPOST LOGIC - Spike Forum
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen jetzt das globale PDO Objekt aus der db.php
global $db;

// Identitäts-Check (Konsistent mit index.php)
$finalID = (int)($_SESSION['user_id'] ?? 0);
$finalPriv = (int)($_SESSION['priv_level'] ?? 0);

// Logik Start
$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) { 
    header("Location: index.php?p=forum"); 
    exit; 
}

// 1. Post laden via PDO Prepared Statement
$stmt = $db->prepare("
    SELECT p.*, t.id as thread_id 
    FROM spike_posts p 
    JOIN spike_threads t ON p.thread_id = t.id 
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post_data = $stmt->fetch();

if (!$post_data) { 
    die("Post not found in Chronicles."); 
}

// 2. Berechtigung prüfen
$is_author = ($finalID > 0 && $finalID === (int)$post_data['author_id']);
$is_admin  = ($finalPriv >= 4); 

if (!$is_author && !$is_admin) { 
    aldhran_log("SECURITY_ALERT", "Unauthorized edit attempt on Post #$post_id", $finalID);
    die("Access denied."); 
}

// 3. Speichern Logik
if (isset($_POST['save_edit'])) {
    // Enterprise V2: CSRF Validation
    checkToken($_POST['csrf_token'] ?? '');

    $new_content = trim($_POST['content'] ?? '');
    
    if (empty($new_content)) {
        die("Chronicles cannot be empty.");
    }

    try {
        $db->beginTransaction();

        // Update via PDO
        $stmt_upd = $db->prepare("UPDATE spike_posts SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt_upd->execute([$new_content, $post_id]);

        // Audit Logging: Wer hat was editiert?
        $log_msg = $is_admin && !$is_author ? "Admin edit by $finalID" : "User edit";
        aldhran_log("POST_EDITED", $log_msg, $finalID, $post_id);

        $db->commit();
        
        header("Location: index.php?p=viewthread&id=" . (int)$post_data['thread_id'] . "&msg=edited");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Edit Post Error: " . $e->getMessage());
        die("The update failed. Contact staff.");
    }
}
?>