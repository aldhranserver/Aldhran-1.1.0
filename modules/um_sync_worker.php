<?php
/**
 * UM SYNC WORKER - ALDHRAN ENTERPRISE
 * Fokus: PDO Migration & Enterprise Logging (Audit Trail)
 * Version: 2.0.0
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Zentrale DB Einbindung (Nutzt jetzt PDO $db)
if (!isset($db)) {
    $db_path = realpath(__DIR__ . '/../includes/db.php');
    if ($db_path && file_exists($db_path)) { 
        require_once($db_path); 
    } else { 
        die("Nexus Bridge Error: Database connection lost."); 
    }
}

$myId = (int)($_SESSION['user_id'] ?? 0);
$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$adminName = $_SESSION['username'] ?? 'System'; 

if ($myPriv < 3) { die("Security Protocol: Unauthorized access."); }

/** --- AJAX HANDLER: SEARCH --- **/
if (isset($_POST['um_ajax_search'])) {
    $search = "%" . $_POST['um_ajax_search'] . "%";
    $filter = ($myPriv === 3) ? " AND priv_level <= 3" : "";
    
    $stmt = $db->prepare("SELECT id, username, standing FROM users WHERE username LIKE ? $filter LIMIT 8");
    $stmt->execute([$search]);

    while ($u = $stmt->fetch()) {
        $color = ($u['standing'] >= 3) ? 'var(--error-red)' : 'var(--glow-blue)';
        echo "<div class='result-item' style='border-left: 3px solid $color' onclick='loadUserEditor({$u['id']})'><strong>".h($u['username'])."</strong></div>";
    }
    exit;
}

/** --- AJAX HANDLER: DELETE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'delete_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $target_id = (int)$_POST['target_id'];
    
    $stmt_u = $db->prepare("SELECT username, priv_level FROM users WHERE id = ?");
    $stmt_u->execute([$target_id]);
    $u_data = $stmt_u->fetch();
    
    if (!$u_data) { die("User not found."); }
    if ($myPriv == 4 && (int)$u_data['priv_level'] >= 5) { die("Access Denied."); }

    $u_name = $u_data['username'];
    
    try {
        $db->beginTransaction();

        // Account & User löschen
        $db->prepare("DELETE FROM `account` WHERE `Name` = ?")->execute([$u_name]);
        $db->prepare("DELETE FROM `users` WHERE `id` = ?")->execute([$target_id]);

        // ENTERPRISE LOGGING
        aldhran_log('USER_DELETED', "Admin $adminName deleted user $u_name", $myId, $target_id);

        $db->commit();
        echo "SUCCESS";
    } catch (Exception $e) {
        $db->rollBack();
        die("Delete failed.");
    }
    exit;
}

/** --- FORM SUBMIT: CREATE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'create_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $u_name = trim($_POST['u_name'] ?? '');
    $u_email = trim($_POST['u_email'] ?? '');
    $u_pass = $_POST['u_pass'] ?? '';
    $u_priv = (int)($_POST['u_priv'] ?? 1);
    
    $hashed_pass_cms = aldhran_hash($u_pass);
    
    try {
        $db->beginTransaction();

        $stmt_ins = $db->prepare("INSERT INTO users (username, email, password, priv_level, standing, is_verified) VALUES (?, ?, ?, ?, 0, 1)");
        $stmt_ins->execute([$u_name, $u_email, $hashed_pass_cms, $u_priv]);
        $new_uid = $db->lastInsertId();

        // DOL Sync
        $res = ""; for ($i = 0; $i < strlen($u_pass); $i++) { $res .= chr(0) . $u_pass[$i]; }
        $dol_final_hash = "##" . strtoupper(md5($res));
        
        $db->prepare("INSERT INTO account (Name, Password, Status, PrivLevel, CreationDate) VALUES (?, ?, 1, ?, NOW())")
           ->execute([$u_name, $dol_final_hash, $u_priv]);

        aldhran_log('USER_CREATED', "Admin $adminName created new user $u_name", $myId, $new_uid);

        $db->commit();
        header("Location: ../index.php?p=um&msg=create_success"); 
    } catch (Exception $e) {
        $db->rollBack();
        die("Creation failed.");
    }
    exit;
}

/** --- FORM SUBMIT: UPDATE FULL --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'update_full') {
    $target_id = (int)$_POST['target_id'];
    
    $stmt_curr = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_curr->execute([$target_id]);
    $current = $stmt_curr->fetch();

    if ($current) {
        $u_name = $current['username'];
        $new_stand = (int)$_POST['u_stand'];
        $reason = $_POST['u_reason'] ?? 'No reason provided.';

        try {
            $db->beginTransaction();

            // 1. PASSWORD SYNC
            if (!empty($_POST['u_new_password'])) {
                $plain_pass = $_POST['u_new_password']; 
                $hashed_pass_cms = aldhran_hash($plain_pass);
                
                $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_pass_cms, $target_id]);

                $res = ""; for ($i = 0; $i < strlen($plain_pass); $i++) { $res .= chr(0) . $plain_pass[$i]; }
                $dol_final_hash = "##" . strtoupper(md5($res));
                $db->prepare("UPDATE account SET `Password` = ? WHERE `Name` = ?")->execute([$dol_final_hash, $u_name]);
                
                aldhran_log('PASSWORD_CHANGED_ADMIN', "Admin $adminName forced PW change for $u_name", $myId, $target_id);
            }

            // 2. STATUS & BAN LOGIK
            $dol_status = ($new_stand >= 3) ? 0 : 1;
            $db->prepare("UPDATE account SET Status = ? WHERE Name = ?")->execute([$dol_status, $u_name]);

            if ($new_stand >= 3) {
                $ban_id = "CMS_" . $u_name . "_" . time();
                $db->prepare("INSERT INTO ban (Author, Account, Reason, Ban_ID, DateBan) VALUES (?, ?, ?, ?, NOW())")
                   ->execute([$adminName, $u_name, $reason, $ban_id]);
                
                aldhran_log('USER_BANNED', "Standing set to $new_stand. Reason: $reason", $myId, $target_id);
            }

            // 3. CMS METADATA
            $f_posts = (int)($_POST['forum_posts'] ?? 0);
            $cms_priv = (int)($_POST['u_priv'] ?? $current['priv_level']);

            $db->prepare("UPDATE users SET priv_level = ?, standing = ?, standing_reason = ?, forum_posts = ? WHERE id = ?")
               ->execute([$cms_priv, $new_stand, $reason, $f_posts, $target_id]);

            $db->commit();
            echo "SUCCESS";
        } catch (Exception $e) {
            $db->rollBack();
            die("Update failed: " . $e->getMessage());
        }
        exit;
    }
}