<?php
/**
 * REGISTER LOGIC - Aldhran Freeshard
 * Version: 1.3.0 - SECURITY: Added CSRF Validation
 */

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) return;

$success = "";
$error = "";

if (isset($_POST['register_user'])) {
    // 1. CSRF VALIDIERUNG
    // Prüft, ob das im Formular versteckte Token mit dem in der Session übereinstimmt.
    checkToken($_POST['csrf_token'] ?? '');

    // 2. HONEYPOT SCHUTZ
    if (!empty($_POST['website_hp'])) {
        die("Bot activity detected.");
    }

    $reg_username = trim($_POST['reg_user']);
    $pass         = $_POST['reg_pass'];
    $email        = trim($_POST['reg_email']);
    $http_host    = $_SERVER['HTTP_HOST'];
    
    // Hilfsfunktion für Passwort-Stärke
    function isPasswordStrong($password) {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        return ($uppercase && $lowercase && $number && $specialChars && strlen($password) >= 8);
    }

    // --- 3. VALIDIERUNG ---
    if (!isset($_POST['captcha_input']) || strtoupper($_POST['captcha_input']) !== ($_SESSION['captcha_code'] ?? '')) {
        $error = "The security code is incorrect.";
    } elseif (strlen($reg_username) < 3) {
        $error = "Username too short (min 3).";
    } elseif (!isPasswordStrong($pass)) {
        $error = "Password too weak! Min 8 chars, incl. Upper, Lower, Number and Special char.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // --- 4. DUBLETTEN-CHECK (Sicher via Prepared Statements) ---
        $stmt_check_cms = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check_cms->bind_param("ss", $reg_username, $email);
        $stmt_check_cms->execute();
        $check_cms = $stmt_check_cms->get_result();

        $stmt_check_game = $conn->prepare("SELECT Name FROM account WHERE Name = ?");
        $stmt_check_game->bind_param("s", $reg_username);
        $stmt_check_game->execute();
        $check_game = $stmt_check_game->get_result();
        
        if ($check_cms->num_rows > 0 || $check_game->num_rows > 0) {
            $error = "This identity or email is already taken.";
        } else {
            $v_code = bin2hex(random_bytes(16));
            $cms_hash = password_hash($pass, PASSWORD_BCRYPT);
            
            $conn->begin_transaction();

            try {
                // --- 5. CMS ACCOUNT INSERT (Sicher via Prepared Statements) ---
                $stmt_ins_cms = $conn->prepare("INSERT INTO users (username, email, password, priv_level, standing, verify_code, is_verified, forum_posts) VALUES (?, ?, ?, 1, 0, ?, 0, 0)");
                $stmt_ins_cms->bind_param("ssss", $reg_username, $email, $cms_hash, $v_code);
                $stmt_ins_cms->execute();

                // --- 6. DOL SERVER ACCOUNT (SYNCHRONIZED HASHING) ---
                $res = "";
                for ($i = 0; $i < strlen($pass); $i++) { 
                    $res .= chr(0) . $pass[$i]; 
                }
                
                $hash = strtoupper(md5($res));
                $hash_len = strlen($hash);
                
                for ($i = ($hash_len - 1) & ~1; $i >= 0; $i -= 2) {
                    if (substr($hash, $i, 1) == "0") { 
                        $hash = substr($hash, 0, $i) . substr($hash, $i + 1, $hash_len); 
                    }
                }
                $dol_final_hash = "##" . $hash;

                // --- 7. GAME ACCOUNT INSERT (Sicher via Prepared Statements) ---
                $stmt_ins_dol = $conn->prepare("INSERT INTO account (Name, Password, Email, PrivLevel, Status, Realm, CreationDate) VALUES (?, ?, ?, 1, 1, 1, NOW())");
                $stmt_ins_dol->bind_param("sss", $reg_username, $dol_final_hash, $email);
                $stmt_ins_dol->execute();

                $conn->commit();

                // --- 8. BESTÄTIGUNGS-EMAIL ---
                $subject = "Verify your account - Aldhran Freeshard";
                $v_link  = "http://" . $http_host . "/index.php?p=verify&code=$v_code";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Aldhran Freeshard <aldhranserver@gmail.com>" . "\r\n";

                $message = "
                <html>
                <body style='background-color: #0a0a0a; color: #ccc; font-family: serif; padding: 40px;'>
                    <div style='max-width: 600px; margin: auto; background: #111; border-top: 3px solid #d4af37; padding: 40px; text-align: center;'>
                        <h2 style='color:#d4af37;'>Welcome to Aldhran</h2>
                        <p>The path is almost clear, <strong>" . h($reg_username) . "</strong>.</p>
                        <p>Verify your user to access Aldhran:</p>
                        <div style='margin-top: 30px;'>
                            <a href='$v_link' style='background: #d4af37; color: #000; padding: 12px 30px; text-decoration: none; font-weight: bold; text-transform: uppercase;'>Verify User</a>
                        </div>
                        <p>Please note: Your chosen password is used to login to our GameServer. You can <strong>not</strong> change your password ingame!</p>
                    </div>
                </body>
                </html>";

                mail($email, $subject, $message, $headers);

                header("Location: index.php?p=login&pending=1"); 
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $error = "The activation failed, please contact a staff member on Discord: " . $e->getMessage();
            }
        }
    }
}
$_SESSION['captcha_code'] = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);