<?php
/**
 * PROFILE LOGIC - Aldhran Freeshard
 * Version: 1.3.0 - CLEANED: phpBB Support removed | FIXED: DOL Password Sync
 */

if (!isset($_SESSION['user_id'])) return;
require_once('includes/db.php');

$uid = (int)$_SESSION['user_id'];
$user = $_SESSION['username'];
$u_esc = mysqli_real_escape_string($conn, $user);

// --- 1. SECURITY & STANDING CHECK ---
$stmt_std = $conn->prepare("SELECT standing FROM users WHERE id = ?");
$stmt_std->bind_param("i", $uid);
$stmt_std->execute();
$userData = $stmt_std->get_result()->fetch_assoc();
$myStanding = (int)($userData['standing'] ?? 0);
$is_restricted = ($myStanding >= 3);

// --- 2. FETCH IN-GAME DATA ---
$char_res = $conn->query("SELECT Name, Class, Level, Realm FROM dolcharacters WHERE AccountName = '$u_esc' ORDER BY Level DESC");
$my_chars = [];
if ($char_res) {
    while ($row = $char_res->fetch_assoc()) { $my_chars[] = $row; }
}

// --- 3. AVATAR UPLOAD ---
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && !$is_restricted) {
    $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($fileExtension, $allowed)) {
        $uploadDir = 'assets/img/avatars/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $newFile = 'avatar_' . $uid . '_' . time() . '.' . $fileExtension;
        $dest = $uploadDir . $newFile;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            $old = $conn->query("SELECT avatar_url FROM users WHERE id = $uid")->fetch_assoc();
            if (!empty($old['avatar_url']) && file_exists($old['avatar_url'])) { @unlink($old['avatar_url']); }
            $conn->query("UPDATE users SET avatar_url = '$dest' WHERE id = $uid");
            header("Location: index.php?p=profile&msg=success"); exit;
        }
    }
}

// --- 4. PROFILE UPDATE (DOL SYNC) ---
if (isset($_POST['update_profile']) && !$is_restricted) {
    $langs = mysqli_real_escape_string($conn, trim($_POST['u_langs']));
    $desc  = mysqli_real_escape_string($conn, trim($_POST['u_desc']));
    $sig   = mysqli_real_escape_string($conn, trim($_POST['u_sig']));
    $new_pw = $_POST['new_pw'];

    // CMS Update
    $conn->query("UPDATE users SET languages = '$langs', description = '$desc', forum_signature = '$sig' WHERE id = $uid");

    // DOL Password Change
    if (!empty($new_pw) && strlen($new_pw) >= 6) {
        $hash = password_hash($new_pw, PASSWORD_BCRYPT);
        
        // 1. Update CMS Password
        $conn->query("UPDATE users SET password = '$hash' WHERE id = $uid");

        // 2. DOL-Specific MD5 Hash Generation (The chr(0) trick for DOL)
        $dol_res = "";
        for ($i = 0; $i < strlen($new_pw); $i++) {
            $dol_res .= chr(0) . $new_pw[$i];
        }
        $dol_final_hash = "##" . strtoupper(md5($dol_res));

        // 3. Update DOL Account Table (Standard & Fallback)
        $conn->query("UPDATE account SET `Password` = '$dol_final_hash', `Status` = 0 WHERE `Name` = '$u_esc'");
        
        if ($conn->affected_rows === 0) {
            $conn->query("UPDATE account SET `Password` = '$dol_final_hash', `Status` = 0 WHERE LOWER(`Name`) = LOWER('$u_esc')");
        }
    }
    header("Location: index.php?p=profile&msg=success"); exit;
}

// Avatar Delete
if (isset($_GET['delete_my_avatar']) && !$is_restricted) {
    $old = $conn->query("SELECT avatar_url FROM users WHERE id = $uid")->fetch_assoc();
    if (!empty($old['avatar_url']) && file_exists($old['avatar_url'])) { @unlink($old['avatar_url']); }
    $conn->query("UPDATE users SET avatar_url = '' WHERE id = $uid");
    header("Location: index.php?p=profile&msg=success"); exit;
}