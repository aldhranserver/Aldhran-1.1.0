<?php
/**
 * UM SYNC WORKER - NEXUS AUTHORIZED VERSION
 * Fokus: Präzise Passwort-Synchronisation & Spike Forum Integration
 * Stand: 27.02.2026 - FINAL: Ban-Trigger for Standing >= 3 Restored
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($conn)) {
    $db_path = realpath(__DIR__ . '/../includes/db.php');
    if ($db_path && file_exists($db_path)) { 
        require_once($db_path); 
    } else { 
        die("Nexus Bridge Error: Database connection lost."); 
    }
}

$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$adminName = $_SESSION['username'] ?? 'System'; 

if ($myPriv < 3) { die("Security Protocol: Unauthorized access."); }

if (!function_exists('getStandingText')) {
    function getStandingText($level) {
        $texts = [0 => "Good", 1 => "Warning I", 2 => "Warning II", 3 => "Restricted", 4 => "Suspended", 5 => "Banned"];
        return $texts[$level] ?? "Unknown ($level)";
    }
}

/** --- AJAX HANDLER: SEARCH --- **/
if (isset($_POST['um_ajax_search'])) {
    $search = mysqli_real_escape_string($conn, $_POST['um_ajax_search']);
    $filter = ($myPriv === 3) ? " AND priv_level <= 3" : "";
    $res = $conn->query("SELECT id, username, standing FROM users WHERE username LIKE '%$search%' $filter LIMIT 8");
    if ($res) {
        while ($u = $res->fetch_assoc()) {
            $color = ($u['standing'] >= 3) ? 'var(--error-red)' : 'var(--glow-blue)';
            echo "<div class='result-item' style='border-left: 3px solid $color' onclick='loadUserEditor({$u['id']})'><strong>".htmlspecialchars($u['username'])."</strong></div>";
        }
    }
    exit;
}

/** --- AJAX HANDLER: LIST CATEGORIES --- **/
if (isset($_POST['um_load_cat'])) {
    $cat = $_POST['um_load_cat'];
    $where = "1=1";
    if($cat == 'restricted') $where = "standing >= 3";
    if($cat == 'warned')     $where = "standing BETWEEN 1 AND 2";
    if($cat == 'staff')      $where = "priv_level >= 3";
    if ($myPriv === 3) { $where .= " AND priv_level <= 3"; }

    $res = $conn->query("SELECT id, username, standing, priv_level, forum_posts FROM users WHERE $where ORDER BY id DESC LIMIT 50");
    echo "<table class='um-table'><thead><tr><th>User</th><th>Standing</th><th>BS</th><th style='text-align:center;'>Posts</th><th>Action</th></tr></thead><tbody>";
    while ($u = $res->fetch_assoc()) {
        $row_style = ($u['standing'] >= 3) ? 'color: var(--error-red);' : '';
        echo "<tr style='$row_style'>
                <td>".htmlspecialchars($u['username'])."</td>
                <td>".getStandingText($u['standing'])."</td>
                <td>".(int)$u['priv_level']."</td>
                <td style='text-align:center; color:var(--glow-blue); font-weight:bold;'>".(int)($u['forum_posts'] ?? 0)."</td>
                <td><button onclick='loadUserEditor(".$u['id'].")' class='btn-nexus-edit'>EDIT</button></td>
              </tr>";
    }
    echo "</tbody></table>";
    exit;
}

/** --- AJAX HANDLER: LOAD EDITOR --- **/
if (isset($_POST['um_ajax_get_editor'])) {
    $id = (int)$_POST['um_ajax_get_editor'];
    $u_data = $conn->query("SELECT u.*, a.PrivLevel as ingame_priv FROM users u 
    LEFT JOIN account a ON u.username = a.Name 
    WHERE u.id = $id")->fetch_assoc();
    
    if ($u_data) { 
        if ($myPriv === 3 && (int)$u_data['priv_level'] > 3) {
            die("<div class='error-msg' style='padding:20px; color:red;'>Access Denied.</div>");
        }
        include(__DIR__ . '/um_editor_view.php'); 
    }
    exit;
}

/** --- AJAX HANDLER: DELETE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'delete_user') {
    if ($myPriv < 4) { die("Restricted: BS 3 cannot delete users."); }
    $target_id = (int)$_POST['target_id'];
    $u_res = $conn->query("SELECT username, priv_level FROM users WHERE id = $target_id");
    $u_data = $u_res->fetch_assoc();
    if (!$u_data) { die("User not found."); }
    if ($myPriv == 4 && (int)$u_data['priv_level'] >= 5) { die("Security Protocol: Cannot delete superior administrators."); }

    $u_name = mysqli_real_escape_string($conn, $u_data['username']);
    $conn->query("DELETE FROM `account` WHERE `Name` = '$u_name'");
    $conn->query("DELETE FROM `ban` WHERE `Account` = '$u_name'");
    if ($conn->query("DELETE FROM `users` WHERE `id` = $target_id")) { echo "SUCCESS"; }
    exit;
}

/** --- FORM SUBMIT: CREATE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'create_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $u_name = mysqli_real_escape_string($conn, trim($_POST['u_name'] ?? ''));
    $u_email = mysqli_real_escape_string($conn, trim($_POST['u_email'] ?? ''));
    $u_pass = $_POST['u_pass'] ?? '';
    $u_priv = (int)($_POST['u_priv'] ?? 1);
    $hashed_pass_cms = password_hash($u_pass, PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (username, email, password, priv_level, standing, is_verified, forum_posts) VALUES ('$u_name', '$u_email', '$hashed_pass_cms', $u_priv, 0, 1, 0)");
    $res = ""; for ($i = 0; $i < strlen($u_pass); $i++) { $res .= chr(0) . $u_pass[$i]; }
    $dol_final_hash = "##" . strtoupper(md5($res));
    $conn->query("INSERT INTO account (Name, Password, Status, PrivLevel, CreationDate) VALUES ('$u_name', '$dol_final_hash', 1, $u_priv, NOW())");
    header("Location: ../index.php?p=um&msg=create_success"); exit;
}

/** --- FORM SUBMIT: UPDATE FULL --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'update_full') {
    $target_id = (int)$_POST['target_id'];
    $current = $conn->query("SELECT * FROM users WHERE id = $target_id")->fetch_assoc();

    if ($current) {
        $u_name = mysqli_real_escape_string($conn, $current['username']);
        $new_stand = (int)$_POST['u_stand'];
        $reason = mysqli_real_escape_string($conn, $_POST['u_reason'] ?? 'No reason provided.');

        // 1. PASSWORD SYNC
        if (!empty($_POST['u_new_password'])) {
            $plain_pass = $_POST['u_new_password'];
            $hashed_pass_cms = password_hash($plain_pass, PASSWORD_BCRYPT);
            $conn->query("UPDATE users SET password = '$hashed_pass_cms' WHERE id = $target_id");
            $res = ""; for ($i = 0; $i < strlen($plain_pass); $i++) { $res .= chr(0) . $plain_pass[$i]; }
            $dol_final_hash = "##" . strtoupper(md5($res));
            $conn->query("UPDATE account SET `Password` = '$dol_final_hash' WHERE `Name` = '$u_name'");
        }

        // 2. DOL SYNC & BAN TRIGGER
        $dol_status = ($new_stand >= 3) ? 0 : 1;
        $dol_priv   = (int)($_POST['u_ingame_priv'] ?? 1); 
        $conn->query("UPDATE account SET Status = $dol_status, PrivLevel = $dol_priv WHERE Name = '$u_name'");

        // Ban-Tabelle Logik
        $conn->query("DELETE FROM `ban` WHERE `Account` = '$u_name'");
        if ($new_stand >= 3) {
            $ban_id = "CMS_" . $u_name . "_" . time();
            $stmt_ban = $conn->prepare("INSERT INTO ban (Author, Type, Ip, Account, DateBan, Reason, LastTimeRowUpdated, Ban_ID) VALUES (?, 'A', '0.0.0.0', ?, NOW(), ?, NOW(), ?)");
            $stmt_ban->bind_param("ssss", $adminName, $u_name, $reason, $ban_id);
            $stmt_ban->execute();
        }

        // 3. CMS UPDATE (Inkl. Spike Forum Meta)
        $avatar_sql = "";
        if (isset($_FILES['u_avatar']) && $_FILES['u_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadFileDir = '../assets/img/avatars/';
            $newFileName = 'avatar_' . $target_id . '_' . time() . '.' . pathinfo($_FILES['u_avatar']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['u_avatar']['tmp_name'], $uploadFileDir . $newFileName)) { $avatar_sql = ", avatar_url = 'assets/img/avatars/$newFileName'"; }
        }

        $f_posts = (int)($_POST['forum_posts'] ?? 0);
        $f_sig = mysqli_real_escape_string($conn, $_POST['forum_signature'] ?? '');
        $bio = mysqli_real_escape_string($conn, $_POST['u_bio'] ?? '');
        $new_title = mysqli_real_escape_string($conn, $_POST['u_title'] ?? '');
        $cms_priv = (int)($_POST['u_priv'] ?? $current['priv_level']);

        if ($myPriv === 3) {
            $conn->query("UPDATE users SET standing = $new_stand, standing_reason = '$reason' $avatar_sql WHERE id = $target_id");
        } else {
            $conn->query("UPDATE users SET priv_level = $cms_priv, standing = $new_stand, standing_reason = '$reason', biography = '$bio', user_title = '$new_title', forum_posts = $f_posts, forum_signature = '$f_sig' $avatar_sql WHERE id = $target_id");
        }
        exit;
    }
}