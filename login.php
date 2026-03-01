<?php
/**
 * Aldhran Enterprise - Login Module
 * Version: 2.1.1 - SECURITY: PDO Migration, CSRF | DESIGN: Integrated View with Logo
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

// --- 1. ADMIN BACKDOOR (PDO Version) ---
$secret_key = "Aldhran2026"; 
if (isset($_GET['unlock']) && $_GET['unlock'] === $secret_key) {
    $stmt_unlock = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt_unlock->execute([$user_ip]);
    
    aldhran_log("ADMIN_BYPASS", "IP restrictions cleared via backdoor", null);
    $info = "Admin bypass active: IP restrictions cleared.";
}

// --- 2. SECURITY CHECK (PDO Version) ---
$db_attempts = 0;
$stmt_check = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
$stmt_check->execute([$user_ip]);
$attempt_data = $stmt_check->fetch();

if ($attempt_data) {
    $last_time = strtotime($attempt_data['last_attempt']);
    $diff = (time() - $last_time) / 60;

    if ($diff < $lockout_min && $attempt_data['attempts'] >= $max_attempts) {
        $wait = ceil($lockout_min - $diff);
        $error = "Security lockout active. Please wait $wait minutes before retrying.";
        aldhran_log("BRUTE_FORCE_BLOCK", "IP $user_ip locked out");
    } elseif ($diff >= $lockout_min) {
        $stmt_reset = $db->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip_address = ?");
        $stmt_reset->execute([$user_ip]);
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
    // CSRF Check
    checkToken($_POST['csrf_token'] ?? '');

    $user_name = trim($_POST['username'] ?? '');
    $pass_raw = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT id, username, password, priv_level, standing FROM users WHERE username = ?");
    $stmt->execute([$user_name]);
    $u = $stmt->fetch();

    if ($u && aldhran_verify($pass_raw, $u['password'])) {
        $stmt_del = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt_del->execute([$user_ip]);

        $_SESSION['user_id'] = (int)$u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['priv_level'] = (int)$u['priv_level'];
        $_SESSION['user_standing'] = (int)$u['standing'];

        aldhran_log("LOGIN_SUCCESS", "User logged in", $u['id']);

        header("Location: index.php");
        exit;
    } else {
        $db_attempts++;
        $stmt_upd = $db->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt_upd->execute([$user_ip]);
        
        aldhran_log("LOGIN_FAILED", "Failed login for: $user_name");
        $error = "Invalid credentials. Remaining attempts: " . ($max_attempts - $db_attempts);
    }
}
$display_remaining = $max_attempts - $db_attempts;

// --- 4. VIEW (HTML AUSGABE) ---
?>

<div class="um-nexus-wrapper" style="max-width: 450px; margin: 10vh auto;">
    <?php if (!empty($error)): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid #ff4444; color: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85em; text-align: center;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
        <div style="background: rgba(0, 212, 255, 0.1); border: 1px solid var(--glow-blue); color: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85em; text-align: center;">
            <i class="fas fa-info-circle"></i> <?php echo h($info); ?>
        </div>
    <?php endif; ?>

    <div class="admin-box" style="border: 1px solid rgba(197, 160, 89, 0.1); background: rgba(5, 5, 5, 0.98); padding: 40px; border-top: 3px solid var(--glow-gold); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

            <div style="margin-bottom: 25px;">
                <label style="color: #555; font-size: 0.65em; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 8px;">Username</label>
                <input type="text" name="username" class="um-input-search-glow" style="width: 100%; padding: 12px; background: #000; color: #fff; border: 1px solid #1a1a1a;" required autofocus>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="color: #555; font-size: 0.65em; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 8px;">Password</label>
                <input type="password" name="password" class="um-input-search-glow" style="width: 100%; padding: 12px; background: #000; color: #fff; border: 1px solid #1a1a1a;" required>
            </div>

            <button type="submit" class="btn-gold" style="width: 100%; padding: 12px; font-family: 'Cinzel'; letter-spacing: 2px; cursor: pointer;">
                AUTHENTICATE
            </button>
        </form>
    </div>
</div>