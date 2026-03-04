<?php
/**
 * PROFILE LOGIC - NeoDOL Standalone
 * Version: 2.1.0 - Standalone Profile Management
 */

if (!isset($_SESSION['user_id'])) return;
require_once('includes/db.php'); 

$uid = (int)$_SESSION['user_id'];
$user = $_SESSION['username'];

// --- 1. SECURITY & STANDING CHECK ---
$stmt_std = $db->prepare("SELECT standing, avatar_url FROM users WHERE id = ?");
$stmt_std->execute([$uid]);
$userData = $stmt_std->fetch();

$myStanding = (int)($userData['standing'] ?? 0);
$is_restricted = ($myStanding >= 3); // Restricted ab Standing 3

// --- 2. AVATAR UPLOAD ---
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && !$is_restricted) {
    checkToken($_POST['csrf_token'] ?? '');
    
    $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($fileExtension, $allowed)) {
        $uploadDir = 'assets/img/avatars/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $newFile = 'avatar_' . $uid . '_' . time() . '.' . $fileExtension;
        $dest = $uploadDir . $newFile;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            if (!empty($userData['avatar_url']) && file_exists($userData['avatar_url'])) { @unlink($userData['avatar_url']); }
            
            $stmt_upd_av = $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $stmt_upd_av->execute([$dest, $uid]);
            
            aldhran_log("AVATAR_CHANGE", "User updated avatar", $uid);
            header("Location: index.php?p=profile&msg=success"); exit;
        }
    }
}

// --- 3. PROFILE UPDATE ---
if (isset($_POST['update_profile']) && !$is_restricted) {
    checkToken($_POST['csrf_token'] ?? '');
    
    $langs = trim($_POST['u_langs'] ?? '');
    $desc  = trim($_POST['u_desc'] ?? '');
    $sig   = trim($_POST['u_sig'] ?? '');
    $new_pw = $_POST['new_pw'] ?? '';

    try {
        $db->beginTransaction();

        $stmt_cms_upd = $db->prepare("UPDATE users SET languages = ?, description = ?, forum_signature = ? WHERE id = ?");
        $stmt_cms_upd->execute([$langs, $desc, $sig, $uid]);

        if (!empty($new_pw) && strlen($new_pw) >= 6) {
            $hash = aldhran_hash($new_pw);
            $stmt_pw = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_pw->execute([$hash, $uid]);
            aldhran_log("PASSWORD_CHANGE", "User changed password", $uid);
        }

        $db->commit();
        header("Location: index.php?p=profile&msg=success"); exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Profile Update Error: " . $e->getMessage());
        header("Location: index.php?p=profile&msg=error"); exit;
    }
}

// Avatar Delete
if (isset($_GET['delete_my_avatar']) && !$is_restricted) {
    if (!empty($userData['avatar_url']) && file_exists($userData['avatar_url'])) { @unlink($userData['avatar_url']); }
    $stmt_del_av = $db->prepare("UPDATE users SET avatar_url = '' WHERE id = ?");
    $stmt_del_av->execute([$uid]);
    header("Location: index.php?p=profile&msg=success"); exit;
}