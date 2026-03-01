<?php
/**
 * REGISTER LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Atomic Transactions
 */

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) return;

$success = "";
$error = "";

if (isset($_POST['register_user'])) {
    // 1. CSRF VALIDIERUNG
    checkToken($_POST['csrf_token'] ?? '');

    // 2. HONEYPOT SCHUTZ
    if (!empty($_POST['website_hp'])) {
        aldhran_log("BOT_ATTEMPT", "Honeypot triggered by IP");
        die("Bot activity detected.");
    }

    $reg_username = trim($_POST['reg_user']);
    $pass         = $_POST['reg_pass'];
    $email        = trim($_POST['reg_email']);
    
    // Hilfsfunktion für Passwort-Stärke (Bleibt gleich)
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
        // --- 4. DUBLETTEN-CHECK via PDO ---
        $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->execute([$reg_username, $email]);
        
        $stmt_check_game = $db->prepare("SELECT Name FROM account WHERE Name = ?");
        $stmt_check_game->execute([$reg_username]);

        if ($stmt_check->fetch() || $stmt_check_game->fetch()) {
            $error = "This identity or email is already taken.";
        } else {
            $v_code = bin2hex(random_bytes(16));
            $cms_hash = aldhran_hash($pass);
            
            // ATOMARE TRANSAKTION (Alles oder nichts)
            try {
                $db->beginTransaction();

                // --- 5. CMS ACCOUNT INSERT ---
                $stmt_ins_cms = $db->prepare("INSERT INTO users (username, email, password, priv_level, standing, verify_code, is_verified) VALUES (?, ?, ?, 1, 0, ?, 0)");
                $stmt_ins_cms->execute([$reg_username, $email, $cms_hash, $v_code]);
                $new_uid = $db->lastInsertId();

                // --- 6. DOL SERVER ACCOUNT (MD5 Padding) ---
                $res = "";
                for ($i = 0; $i < strlen($pass); $i++) { $res .= chr(0) . $pass[$i]; }
                $hash = strtoupper(md5($res));
                $hash_len = strlen($hash);
                for ($i = ($hash_len - 1) & ~1; $i >= 0; $i -= 2) {
                    if (substr($hash, $i, 1) == "0") { $hash = substr($hash, 0, $i) . substr($hash, $i + 1, $hash_len); }
                }
                $dol_final_hash = "##" . $hash;

                // --- 7. GAME ACCOUNT INSERT ---
                $stmt_ins_dol = $db->prepare("INSERT INTO account (Name, Password, Email, PrivLevel, Status, Realm, CreationDate) VALUES (?, ?, ?, 1, 0, 1, NOW())");
                $stmt_ins_dol->execute([$reg_username, $dol_final_hash, $email]);

                // LOGGING
                aldhran_log("USER_REGISTERED", "New account pending verification", $new_uid);

                $db->commit();

                // --- 8. BESTÄTIGUNGS-EMAIL ---
                $subject = "Verify your account - Aldhran Freeshard";
                $v_link  = SITE_URL . "/index.php?p=verify&code=$v_code";
                
                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Aldhran Freeshard <aldhranserver@gmail.com>\r\n";

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
                    </div>
                </body>
                </html>";

                mail($email, $subject, $message, $headers);

                header("Location: index.php?p=login&pending=1"); 
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Registration Error: " . $e->getMessage());
                $error = "The ritual failed: " . $e->getMessage();
            }
        }
    }
}
$_SESSION['captcha_code'] = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);