<?php
/**
 * DAoC Portal NR - Register
 * Version: 1.0.1 - Space Prevention Added
 */
require_once('../includes/db.php');

if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

$msg = "";
$error = "";

if (isset($_POST['register'])) {
    $user = trim($_POST['user']);
    $mail = trim($_POST['mail']);
    $pass = $_POST['pass'];

    // Check if user exists
    $check = $db->prepare("SELECT id FROM portal_users WHERE username = ? OR email = ?");
    $check->execute([$user, $mail]);
    
    if ($check->rowCount() > 0) {
        $error = "Username or Email already taken.";
    } else {
        $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO portal_users (username, password, email) VALUES (?, ?, ?)");
        if ($stmt->execute([$user, $hashed_pass, $mail])) {
            $msg = "Account created! You can now <a href='portal_login.php' style='color:#fff;'>Login</a>.";
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portal Registration - DAoC Portal NR</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .nexus-window { width: 400px; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 40px; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        h2 { font-family: 'Cinzel', serif; color: #c5a059; text-align: center; margin-bottom: 30px; letter-spacing: 2px; }
        input { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; margin-bottom: 20px; box-sizing: border-box; outline: none; }
        input:focus { border-color: #c5a059; }
        .btn-nexus { width: 100%; padding: 15px; background: transparent; border: 1px solid #c5a059; color: #c5a059; cursor: pointer; font-family: 'Cinzel'; font-weight: bold; transition: 0.3s; }
        .btn-nexus:hover { background: #c5a059; color: #000; }
        .alert { text-align: center; margin-bottom: 20px; font-size: 13px; padding: 10px; }
        .error { color: #ff4444; border: 1px solid #440000; background: rgba(255,0,0,0.05); }
        .success { color: #00ff00; border: 1px solid #004400; background: rgba(0,255,0,0.05); }
        .links { text-align: center; margin-top: 20px; font-size: 12px; }
        .links a { color: #666; text-decoration: none; }
        .links a:hover { color: #c5a059; }
    </style>
</head>
<body>

<div class="nexus-window">
    <h2>Portal Register</h2>
    
    <?php if($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
    <?php if($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
    
    <form method="POST">
        <input type="text" name="user" placeholder="Username (No spaces)" pattern="^\S+$" title="Spaces are not allowed" required>
        <input type="email" name="mail" placeholder="Email Address" required>
        <input type="password" name="pass" placeholder="Password" required>
        <button type="submit" name="register" class="btn-nexus">CREATE ACCOUNT</button>
    </form>

    <div class="links">
        <a href="portal_login.php">Already have an account? Sign in</a><br><br>
        <a href="index.php" style="font-size: 10px; opacity: 0.5;">&laquo; Back to Portal</a>
    </div>
</div>

</body>
</html>