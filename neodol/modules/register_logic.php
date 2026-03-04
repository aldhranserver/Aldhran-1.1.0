<?php
/**
 * REGISTER LOGIC - NeoDOL Standalone
 * Version: 2.1.0 - Clean Standalone Registration
 */

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) return;

$success = "";
$error = "";

if (isset($_POST['register_user'])) {
    checkToken($_POST['csrf_token'] ?? '');

    if (!empty($_POST['website_hp'])) {
        aldhran_log("BOT_ATTEMPT", "Honeypot triggered");
        die("Bot activity detected.");
    }

    $reg_username = trim($_POST['reg_user']);
    $pass         = $_POST['reg_pass'];
    $email        = trim($_POST['reg_email']);
    
    function isPasswordStrong($password) {
        return (strlen($password) >= 8 && preg_match('@[A-Z]@', $password) && preg_match('@[0-9]@', $password));
    }

    if (!isset($_POST['captcha_input']) || strtoupper($_POST['captcha_input']) !== ($_SESSION['captcha_code'] ?? '')) {
        $error = "The security code is incorrect.";
    } elseif (strlen($reg_username) < 3) {
        $error = "Username too short.";
    } elseif (!isPasswordStrong($pass)) {
        $error = "Password too weak! Min 8 chars, incl. Upper and Number.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->execute([$reg_username, $email]);

        if ($stmt_check->fetch()) {
            $error = "Username or Email already taken.";
        } else {
            $v_code = bin2hex(random_bytes(16));
            $cms_hash = aldhran_hash($pass);
            
            try {
                $stmt_ins = $db->prepare("INSERT INTO users (username, email, password, priv_level, standing, verify_code, is_verified) VALUES (?, ?, ?, 1, 0, ?, 1)"); // Sofort verifiziert für Lokal-Tests
                $stmt_ins->execute([$reg_username, $email, $cms_hash, $v_code]);
                $new_uid = $db->lastInsertId();

                aldhran_log("USER_REGISTERED", "New standalone account created", $new_uid);
                header("Location: index.php?p=login&msg=reg_success"); 
                exit;

            } catch (Exception $e) {
                error_log("Registration Error: " . $e->getMessage());
                $error = "The ritual failed: Database error.";
            }
        }
    }
}
$_SESSION['captcha_code'] = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);