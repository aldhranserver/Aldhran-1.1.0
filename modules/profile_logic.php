<?php
/**
 * PROFILE LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Atomic Sync
 */

if (!isset($_SESSION['user_id'])) return;
require_once('includes/db.php'); // Nutzt das globale $db PDO-Objekt

$uid = (int)$_SESSION['user_id'];
$user = $_SESSION['username'];

// --- 1. SECURITY & STANDING CHECK (PDO) ---
$stmt_std = $db->prepare("SELECT standing FROM users WHERE id = ?");
$stmt_std->execute([$uid]);
$userData = $stmt_std->fetch();

$myStanding = (int)($userData['standing'] ?? 0);
$is_restricted = ($myStanding >= 3);

// --- 2. FETCH IN-GAME DATA (PDO) ---
$stmt_char = $db->prepare("SELECT Name, Class, Level, Realm FROM dolcharacters WHERE AccountName = ? ORDER BY Level DESC");
$stmt_char->execute([$user]);
$my_chars = $stmt_char->fetchAll();

// --- 3. AVATAR UPLOAD ---
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
            // Alten Avatar sicher löschen
            $stmt_old = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt_old->execute([$uid]);
            $old = $stmt_old->fetch();
            
            if (!empty($old['avatar_url']) && file_exists($old['avatar_url'])) { @unlink($old['avatar_url']); }
            
            // Datenbank-Update via PDO
            $stmt_upd_av = $db->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $stmt_upd_av->execute([$dest, $uid]);
            
            aldhran_log("AVATAR_CHANGE", "User updated avatar", $uid);
            header("Location: index.php?p=profile&msg=success"); exit;
        }
    }
}

// --- 4. PROFILE UPDATE (DOL SYNC) ---
if (isset($_POST['update_profile']) && !$is_restricted) {
    checkToken($_POST['csrf_token'] ?? '');
    
    $langs = trim($_POST['u_langs'] ?? '');
    $desc  = trim($_POST['u_desc'] ?? '');
    $sig   = trim($_POST['u_sig'] ?? '');
    $new_pw = $_POST['new_pw'] ?? '';

    try {
        $db->beginTransaction();

        // CMS Profile Update
        $stmt_cms_upd = $db->prepare("UPDATE users SET languages = ?, description = ?, forum_signature = ? WHERE id = ?");
        $stmt_cms_upd->execute([$langs, $desc, $sig, $uid]);

        // Passwort-Synchronisation
        if (!empty($new_pw) && strlen($new_pw) >= 6) {
            // 1. CMS Password (Bcrypt + Pepper)
            $hash = aldhran_hash($new_pw);
            $stmt_pw = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_pw->execute([$hash, $uid]);

            // 2. DOL-Specific MD5 Hash Generation
            $dol_res = "";
            for ($i = 0; $i < strlen($new_pw); $i++) {
                $dol_res .= chr(0) . $new_pw[$i];
            }
            // Das MD5 Padding muss genau so bleiben für den Game-Client
            $dol_final_hash = "##" . strtoupper(md5($dol_res));

            // 3. Update DOL Account Table
            $stmt_dol = $db->prepare("UPDATE account SET Password = ?, Status = 0 WHERE Name = ?");
            $stmt_dol->execute([$dol_final_hash, $user]);
            
            aldhran_log("PASSWORD_CHANGE", "User changed account password", $uid);
        }

        $db->commit();
        header("Location: index.php?p=profile&msg=success"); exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Profile Update Error: " . $e->getMessage());
        header("Location: index.php?p=profile&msg=error"); exit;
    }
}

// Avatar Delete
if (isset($_GET['delete_my_avatar']) && !$is_restricted) {
    $stmt_old = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
    $stmt_old->execute([$uid]);
    $old = $stmt_old->fetch();
    
    if (!empty($old['avatar_url']) && file_exists($old['avatar_url'])) { @unlink($old['avatar_url']); }
    
    $stmt_del_av = $db->prepare("UPDATE users SET avatar_url = '' WHERE id = ?");
    $stmt_del_av->execute([$uid]);
    
    header("Location: index.php?p=profile&msg=success"); exit;
}