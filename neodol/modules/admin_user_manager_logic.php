<?php
/**
 * ADMIN USER MANAGER LOGIC - NeoDOL Standalone
 * Version: 1.0.0
 */
if (!isset($db)) require_once('includes/db.php');

// Sicherheitscheck: Nur Admins (Priv >= 4) dürfen hier agieren
$checkPriv = (int)($_SESSION['priv_level'] ?? 0);
if ($checkPriv < 4) {
    die("Unauthorized access to nexus core.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $action = $_POST['action'];

    // 1. User im CMS identifizieren
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $user_data = $stmt->fetch();

    if (!$user_data) {
        header("Location: index.php?p=permissions&err=user_not_found");
        exit;
    }

    // 2. Logik basierend auf der Aktion
    switch ($action) {
        case 'update_standing':
            $new_standing = $_POST['standing']; // z.B. 'Active', 'Suspended'
            $stmt_upd = $db->prepare("UPDATE users SET standing = ? WHERE id = ?");
            $stmt_upd->execute([$new_standing, $target_user_id]);
            
            logAction($db, $_SESSION['user_id'], $target_user_id, 'STANDING_CHANGE', "Changed standing to $new_standing");
            header("Location: index.php?p=permissions&msg=success");
            break;

        case 'change_privilege':
            $new_priv = (int)$_POST['priv_level'];
            $stmt_priv = $db->prepare("UPDATE users SET priv_level = ? WHERE id = ?");
            $stmt_priv->execute([$new_priv, $target_user_id]);
            
            logAction($db, $_SESSION['user_id'], $target_user_id, 'PRIV_CHANGE', "Set PrivLevel to $new_priv");
            header("Location: index.php?p=permissions&msg=success");
            break;

        case 'delete_account':
            $stmt_del = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt_del->execute([$target_user_id]);
            
            logAction($db, $_SESSION['user_id'], $target_user_id, 'ACCOUNT_DELETED', "Account removed from NeoDOL");
            header("Location: index.php?p=permissions&msg=delete_success");
            break;
    }
    exit;
}