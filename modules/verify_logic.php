<?php
/**
 * VERIFY LOGIC & VIEW - Aldhran Freeshard
 * Version: 1.1.0 - SECURITY: Fully Prepared Statements
 */

// 1. LOGIK-TEIL
$verify_success = false;
$verify_error = false;

if (isset($_GET['code'])) {
    $code = $_GET['code']; // Kein real_escape mehr nötig wegen Prepared Stmts
    
    // Suche nach dem User mit diesem Code (Sicher via Prepared Statement)
    $stmt_find = $conn->prepare("SELECT id, username FROM users WHERE verify_code = ? AND is_verified = 0");
    $stmt_find->bind_param("s", $code);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($res && $res->num_rows > 0) {
        $u = $res->fetch_assoc();
        $uid = (int)$u['id'];
        $uname = $u['username'];
        
        $conn->begin_transaction();

        try {
            // A. CMS & Spike Status auf verifiziert setzen (Sicher via Prepared)
            $stmt_upd_cms = $conn->prepare("UPDATE users SET is_verified = 1, verify_code = NULL WHERE id = ?");
            $stmt_upd_cms->bind_param("i", $uid);
            $stmt_upd_cms->execute();
            
            // B. DOL Spiel-Account aktivieren (Status 1 = Aktiv)
            $stmt_upd_dol = $conn->prepare("UPDATE account SET Status = 1 WHERE Name = ?");
            $stmt_upd_dol->bind_param("s", $uname);
            $stmt_upd_dol->execute();

            // C. Log-Eintrag (Sicher via Prepared in db.php definiert)
            if (function_exists('logAction')) {
                logAction($conn, $uid, $uid, 'USER_VERIFIED', "User '$uname' verified via email.");
            }
            
            $conn->commit();
            $verify_success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $verify_error = "The ritual of verification failed. Please contact the administrators.";
        }
    } else {
        $verify_error = "The verification code is invalid or the account has already been activated.";
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<style>
    .verify-container {
        max-width: 450px; margin: 60px auto; background: rgba(10, 10, 10, 0.95);
        border: 1px solid rgba(212, 175, 55, 0.3); padding: 40px; text-align: center; border-top: 3px solid #d4af37;
    }
    .verify-logo { max-width: 200px; margin-bottom: 30px; }
    .verify-title { color: #d4af37; font-family: 'Cinzel', serif; text-transform: uppercase; letter-spacing: 4px; font-size: 1.4em; margin-bottom: 30px; }
    .verify-status { padding: 20px; font-size: 14px; line-height: 1.6; margin-bottom: 30px; }
    .success-text { color: #00ff00; background: rgba(0, 255, 0, 0.05); border: 1px solid rgba(0, 255, 0, 0.2); padding: 15px; }
    .error-text { color: #ff4444; background: rgba(255, 0, 0, 0.05); border: 1px solid rgba(255, 0, 0, 0.2); padding: 15px; }
    .btn-verify { display: inline-block; width: 100%; padding: 16px; background: transparent; border: 1px solid #d4af37; color: #d4af37; text-transform: uppercase; letter-spacing: 3px; font-weight: bold; text-decoration: none; transition: 0.4s; font-family: 'Cinzel', serif; box-sizing: border-box; }
    .btn-verify:hover { background: #d4af37; color: #000; }
</style>

<div class="verify-container">
    <img src="assets/img/logo.png" alt="Aldhran Logo" class="verify-logo">
    <h2 class="verify-title">Account Verification</h2>

    <div class="verify-status">
        <?php if ($verify_success): ?>
            <div class="success-text">
                <i class="fas fa-check-circle"></i><br><br>
                Thank you! Your account has been successfully verified.<br>
                You will be redirected shortly.
            </div>
            <script>setTimeout(function(){ window.location.href = 'index.php?p=login&verified=1'; }, 3500);</script>
        <?php else: ?>
            <div class="error-text">
                <i class="fas fa-exclamation-triangle"></i><br><br>
                <?php echo h($verify_error); ?>
            </div>
        <?php endif; ?>
    </div>

    <a href="index.php<?php echo (!$verify_success ? '' : '?p=login&verified=1'); ?>" class="btn-verify">
        <?php echo (!$verify_success ? 'Return to Main' : 'Go to Login'); ?>
    </a>
</div>