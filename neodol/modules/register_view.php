<?php
/**
 * REGISTER VIEW - Aldhran Freeshard
 * Version: 0.7.0 - SECURITY: CSRF Token & XSS Protection
 */
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<style>
    .register-container {
        max-width: 450px;
        margin: 60px auto;
        background: rgba(10, 10, 10, 0.95);
        border: 1px solid rgba(212, 175, 55, 0.3);
        padding: 40px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.8), 0 0 10px rgba(212, 175, 55, 0.1);
        text-align: center;
        border-top: 3px solid #d4af37;
    }

    .register-logo {
        max-width: 220px;
        margin-bottom: 30px;
        filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.2));
    }

    .reg-title {
        color: #d4af37;
        font-family: 'Cinzel', serif;
        text-transform: uppercase;
        letter-spacing: 4px;
        font-size: 1.4em;
        margin-bottom: 35px;
        text-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
    }

    .reg-group {
        margin-bottom: 25px;
        text-align: left;
    }

    .reg-group label {
        display: block;
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 8px;
        letter-spacing: 2px;
    }

    .reg-input {
        width: 100%;
        padding: 14px;
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid #222;
        color: #fff;
        box-sizing: border-box;
        transition: 0.3s;
        border-radius: 0;
    }

    .reg-input:focus {
        border-color: #d4af37;
        outline: none;
        background: #000;
        box-shadow: 0 0 8px rgba(212, 175, 55, 0.1);
    }

    .captcha-box {
        background: #050505;
        border: 1px solid #1a1a1a;
        padding: 15px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .captcha-code {
        color: #d4af37;
        font-weight: bold;
        letter-spacing: 4px;
        font-size: 1.2em;
        border-right: 1px solid #222;
        padding-right: 15px;
        user-select: none;
    }

    .btn-register {
        width: 100%;
        padding: 16px;
        background: transparent;
        border: 1px solid #d4af37;
        color: #d4af37;
        text-transform: uppercase;
        letter-spacing: 3px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.4s;
        font-family: 'Cinzel', serif;
    }

    .btn-register:hover {
        background: #d4af37;
        color: #000;
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
    }

    .reg-footer {
        margin-top: 30px;
        font-size: 11px;
    }

    .reg-footer a {
        color: #444;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .reg-footer a:hover {
        color: #d4af37;
    }
</style>

<div class="register-container">
    <img src="assets/img/logo.png" alt="Aldhran Logo" class="register-logo">
    
    <h2 class="reg-title">Create Account</h2>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(255,0,0,0.05); color: #ff4444; padding: 12px; border: 1px solid #600; font-size: 12px; margin-bottom: 25px; text-align: left;">
            <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

        <div style="display:none;">
            <input type="text" name="website_hp" value="">
        </div>
    
        <div class="reg-group">
            <label>Username</label>
            <input type="text" name="reg_user" class="reg-input" required placeholder="Enter username..." autocomplete="off" maxlength="30">
        </div>

        <div class="reg-group">
            <label>Email Address</label>
            <input type="email" name="reg_email" class="reg-input" required placeholder="Enter email...">
        </div>

        <div class="reg-group">
            <label>Password</label>
            <input type="password" name="reg_pass" class="reg-input" required placeholder="Enter password..." minlength="8">
        </div>

        <div class="captcha-box">
            <span class="captcha-code"><?php echo h($_SESSION['captcha_code'] ?? 'ERR'); ?></span>
            <input type="text" name="captcha_input" class="reg-input" style="width: 120px; text-align: center;" placeholder="Code" required autocomplete="off">
        </div>

        <button type="submit" name="register_user" class="btn-register">Register User</button>
    </form>
    
    <div class="reg-footer">
        <a href="index.php?p=login">Already registered? Log in here</a>
    </div>
</div>