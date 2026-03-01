<?php
/**
 * Aldhran Enterprise - Finalize Password Reset
 * Version: 2.0.0 - SECURITY: PDO Migration & Transaction Logging
 */
require_once('includes/db.php');

$error = "";
$success = false;
$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');

if (empty($token)) { header("Location: index.php?p=login"); exit; }

// 1. TOKEN VALIDIERUNG (PDO Syntax)
$stmt = $db->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "The reset link is invalid or has already expired.";
}

// 2. FORMULAR VERARBEITUNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_pass = $_POST['password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 6) {
        $error = "Password too short (min. 6 characters).";
    } elseif ($new_pass !== $conf_pass) {
        $error = "The passwords do not match.";
    } else {
        // CMS HASH (Zentral via db.php)
        $cms_hash = aldhran_hash($new_pass);

        // DOL SYNC HASH (MD5 Padding für Game-Kompatibilität)
        $res = "";
        for ($i = 0; $i < strlen($new_pass); $i++) { $res .= chr(0) . $new_pass[$i]; }
        $hash = strtoupper(md5($res));
        $hash_len = strlen($hash);
        for ($i = ($hash_len - 1) & ~1; $i >= 0; $i -= 2) {
            if (substr($hash, $i, 1) == "0") { $hash = substr($hash, 0, $i) . substr($hash, $i + 1, $hash_len); }
        }
        $dol_final_hash = "##" . $hash;

        // PDO TRANSAKTION (Atomar: Alles oder nichts)
        try {
            $db->beginTransaction();

            // CMS Update
            $upd = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            $upd->execute([$cms_hash, $user['id']]);

            // Game Server Update
            $stmt_game = $db->prepare("UPDATE account SET Password = ? WHERE Name = ?");
            $stmt_game->execute([$dol_final_hash, $user['username']]);

            // ERFOLG LOGGEN
            aldhran_log("PW_RESET_SUCCESS", "Password reset finalized via token", $user['id']);

            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Reset Error: " . $e->getMessage());
            $error = "Synchronization failed. Please contact staff.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aldhran - New Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body.auth-body { background: #050505; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Cinzel', serif; }
        .register-container { max-width: 400px; width: 90%; background: rgba(10, 10, 10, 0.95); border: 1px solid rgba(212, 175, 55, 0.3); padding: 40px; text-align: center; border-top: 3px solid #d4af37; }
        .reg-input { width: 100%; padding: 14px; background: rgba(0,0,0,0.6); border: 1px solid #222; color: #fff; box-sizing: border-box; }
        .btn-register { width: 100%; padding: 16px; background: transparent; border: 1px solid #d4af37; color: #d4af37; text-transform: uppercase; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-register:hover { background: #d4af37; color: #000; }
    </style>
</head>
<body class="auth-body">
<div class="register-container">
    <h2 style="color: #d4af37; text-transform: uppercase; letter-spacing: 3px; font-size: 1.4em; margin-bottom: 30px;">New Password</h2>

    <?php if ($success): ?>
        <div style="color: #00ff00; background: rgba(0, 255, 0, 0.05); padding: 15px; border: 1px solid #060; margin-bottom: 20px; font-size: 0.9em;">Success! Password updated.</div>
        <a href="index.php?p=login" class="btn-register" style="display: block; text-decoration: none; text-align: center;">Login Now</a>
    <?php elseif ($error && !$user): ?>
        <div style="color: #ff4444; background: rgba(255, 0, 0, 0.05); padding: 15px; border: 1px solid #600; margin-bottom: 20px; font-size: 0.8em;"><?php echo $error; ?></div>
        <a href="forgot_password.php" class="btn-register" style="display: block; text-decoration: none; text-align: center;">New Link</a>
    <?php else: ?>
        <?php if ($error): ?><div style="color: #ff4444; font-size: 0.8em; margin-bottom: 15px; text-align: left;"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div style="text-align: left; margin-bottom: 20px;">
                <label style="display: block; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 8px;">New Password</label>
                <input type="password" name="password" class="reg-input" required placeholder="Min. 6 chars">
            </div>
            <div style="text-align: left; margin-bottom: 30px;">
                <label style="display: block; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Confirm Password</label>
                <input type="password" name="confirm_password" class="reg-input" required placeholder="Repeat password">
            </div>
            <button type="submit" class="btn-register">Finalize Reset</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>