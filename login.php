<?php
/**
 * Aldhran - Login Module
 * Version: 1.9.2 - SECURITY: CSRF Protection & Original Design
 */
require_once('includes/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$info = "";
$user_ip = $_SERVER['REMOTE_ADDR']; 
$max_attempts = 3;
$lockout_min = 10;

// --- 1. ADMIN BACKDOOR (Sicherer Prepared-Bypass) ---
$secret_key = "Aldhran2026"; 
if (isset($_GET['unlock']) && $_GET['unlock'] === $secret_key) {
    $stmt_unlock = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt_unlock->bind_param("s", $user_ip);
    $stmt_unlock->execute();
    $info = "Admin bypass active: IP restrictions cleared.";
}

// --- 2. SECURITY CHECK (Prepared) ---
$db_attempts = 0;
$stmt_check = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
$stmt_check->bind_param("s", $user_ip);
$stmt_check->execute();
$attempt_data = $stmt_check->get_result()->fetch_assoc();

if ($attempt_data) {
    $last_time = strtotime($attempt_data['last_attempt']);
    $diff = (time() - $last_time) / 60;

    if ($diff < $lockout_min && $attempt_data['attempts'] >= $max_attempts) {
        $wait = ceil($lockout_min - $diff);
        $error = "Security lockout active. Please wait $wait minutes before retrying.";
    } elseif ($diff >= $lockout_min) {
        $stmt_reset = $conn->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip_address = ?");
        $stmt_reset->bind_param("s", $user_ip);
        $stmt_reset->execute();
    } else {
        $db_attempts = $attempt_data['attempts'];
    }
}

// Notifications
if (isset($_GET['pending']))       $info = "A verification email has been sent.";
if (isset($_GET['verified']))      $info = "Account successfully verified.";
if (isset($_GET['reset_sent']))    $info = "A password reset link has been sent to your email.";
if (isset($_GET['reset_success'])) $info = "Your password has been successfully updated.";

// --- 3. AUTHENTICATION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // CSRF Check (Greift auf die Funktion in db.php zu)
    checkToken($_POST['csrf_token'] ?? '');

    $user_name = trim($_POST['username'] ?? '');
    $pass_raw = $_POST['password'] ?? '';
    // Aldhran V1.1 Security: Peppered Hashing
    $pass = hash_hmac("sha256", $pass_raw, ALDRAN_PEPPER);

    $stmt = $conn->prepare("SELECT id, username, password, email, priv_level, standing, is_verified FROM users WHERE username = ?");
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password'])) {
        $stmt_del = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt_del->bind_param("s", $user_ip);
        $stmt_del->execute();

        $_SESSION['user_id'] = (int)$u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['priv_level'] = (int)$u['priv_level'];
        $_SESSION['user_standing'] = (int)$u['standing'];

        header("Location: index.php");
        exit;
    } else {
        $db_attempts++;
        $stmt_upd = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt_upd->bind_param("s", $user_ip);
        $stmt_upd->execute();
        $error = "Invalid credentials. Remaining attempts: " . ($max_attempts - $db_attempts);
    }
}
$display_remaining = $max_attempts - $db_attempts;
?>

<style>
    .login-container {
        max-width: 400px; margin: 60px auto; background: rgba(10, 10, 10, 0.95);
        border: 1px solid rgba(212, 175, 55, 0.3); padding: 40px; text-align: center; border-top: 3px solid #d4af37;
    }
    .login-logo { max-width: 220px; margin-bottom: 30px; }
    .reg-group { margin-bottom: 25px; text-align: left; }
    .reg-group label { display: block; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 2px; }
    .reg-input { width: 100%; padding: 14px; background: rgba(0, 0, 0, 0.6); border: 1px solid #222; color: #fff; box-sizing: border-box; }
    .btn-login {
        width: 100%; padding: 16px; background: transparent; border: 1px solid #d4af37; color: #d4af37;
        text-transform: uppercase; letter-spacing: 3px; font-weight: bold; cursor: pointer; transition: 0.4s;
    }
    .btn-login:hover:not(:disabled) { background: #d4af37; color: #000; }
    .btn-login:disabled { opacity: 0.3; cursor: not-allowed; }
    .reg-footer { margin-top: 30px; font-size: 11px; display: flex; flex-direction: row; justify-content: center; gap: 15px; }
    .reg-footer a { color: #444; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; }
    .reg-footer a:hover { color: #d4af37; }
</style>

<div class="login-container">
    <img src="assets/img/logo.png" alt="Aldhran Logo" class="login-logo">
    
    <?php if ($info): ?>
        <div style="background: rgba(0, 212, 255, 0.05); color: #00d4ff; padding: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-size: 11px; margin-bottom: 25px; text-align: left;">
            <i class="fas fa-info-circle"></i> <?php echo h($info); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: rgba(255,0,0,0.05); color: #ff4444; padding: 12px; border: 1px solid #600; font-size: 12px; margin-bottom: 25px; text-align: left;">
             <i class="fas fa-exclamation-triangle"></i> <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?p=login">
        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

        <div class="reg-group">
            <label>Username</label>
            <input type="text" name="username" class="reg-input" required autofocus placeholder="Username">
        </div>
        <div class="reg-group">
            <label>Password</label>
            <input type="password" name="password" class="reg-input" required placeholder="Password">
        </div>
        
        <div style="margin-bottom: 15px; text-align: right; height: 15px;">
            <?php if ($display_remaining > 0 && $display_remaining < 3): ?>
                <span style="font-size: 10px; color: #888; letter-spacing: 1px;">
                    <i class="fas fa-shield-alt"></i> SECURITY: <?php echo $display_remaining; ?> ATTEMPTS LEFT
                </span>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn-login" <?php echo ($display_remaining <= 0) ? 'disabled' : ''; ?>>
            <?php echo ($display_remaining <= 0) ? 'LOCKED' : 'Sign In'; ?>
        </button>
    </form>
    
    <div class="reg-footer">
        <a href="forgot_password.php">Recover Password</a>
        <a href="index.php?p=register">Register Account</a>
    </div>
</div>