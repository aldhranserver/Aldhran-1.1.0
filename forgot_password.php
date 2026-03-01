<?php
/**
 * Aldhran Freeshard - Password Reset Request
 * Version: 1.1.2 - SQL Time Fix
 */
require_once('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));

    // Check ob Email existiert
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(32));

        // FIX: Wir lassen MySQL die Stunde addieren, um Synchronität zu wahren
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $update->bind_param("ss", $token, $email);
        $update->execute();

        $subject = "Password Reset - Aldhran Freeshard";
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token; 
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Aldhran Freeshard <aldhranserver@gmail.com>\r\n";

        $message = "
        <html>
        <body style='background-color: #0a0a0a; color: #ccc; font-family: serif; padding: 40px;'>
            <div style='max-width: 600px; margin: auto; background: #111; border-top: 3px solid #d4af37; padding: 40px; text-align: center;'>
                <h2 style='color:#d4af37;'>Account Recovery</h2>
                <p>Hello <strong>" . htmlspecialchars($user['username']) . "</strong>,</p>
                <p>A password reset was requested for your account on Aldhran Freeshard.</p>
                <div style='margin-top: 30px;'>
                    <a href='$resetLink' style='background: #d4af37; color: #000; padding: 12px 30px; text-decoration: none; font-weight: bold; text-transform: uppercase;'>Reset Password</a>
                </div>
                <p style='margin-top: 30px; font-size: 0.8em; color: #444;'>This link is valid for 1 hour.</p>
            </div>
        </body>
        </html>";

        mail($email, $subject, $message, $headers);
    }
    
    header("Location: index.php?p=login&reset_sent=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aldhran Freeshard - Password Reset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        body.auth-body { background: #050505; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .register-container { max-width: 400px; width: 90%; background: rgba(10, 10, 10, 0.95); border: 1px solid rgba(212, 175, 55, 0.3); padding: 40px; text-align: center; border-top: 3px solid #d4af37; }
    </style>
</head>
<body class="auth-body">
<div class="register-container">
    <h2 style="color: #d4af37; font-family: 'Cinzel', serif; text-transform: uppercase; letter-spacing: 3px; font-size: 1.4em; margin-bottom: 30px;">Reset Access</h2>
    <p style="color: #666; font-size: 0.9em; margin-bottom: 30px;">Enter your <b>Email Address</b> to receive a password reset link.</p>
    <form method="POST">
        <div style="text-align: left; margin-bottom: 25px;">
            <label style="display: block; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 2px;">Email Address</label>
            <input type="email" name="email" class="reg-input" required placeholder="name@example.com" style="width: 100%; padding: 14px; background: rgba(0,0,0,0.6); border: 1px solid #222; color: #fff;">
        </div>
        <button type="submit" class="btn-register" style="width: 100%; padding: 16px; background: transparent; border: 1px solid #d4af37; color: #d4af37; text-transform: uppercase; letter-spacing: 3px; font-weight: bold; cursor: pointer;">Request Link</button>
    </form>
    <div style="margin-top: 30px; font-size: 11px;"><a href="index.php?p=login" style="color: #444; text-decoration: none; text-transform: uppercase;">Back to Login</a></div>
</div>
</body>
</html>