<?php
/**
 * UM SYNC WORKER - NEXUS AUTHORIZED PDO VERSION
 * Version: 2.2.7 - FINAL REPAIR: Handler separation & Delete Logic
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 1. DATENBANK & CORE FUNKTIONEN (aldhran_hash)
$db_path = dirname(__DIR__) . '/includes/db.php';
if (file_exists($db_path)) {
    require_once($db_path);
} else {
    die("Nexus Bridge Error: File not found at $db_path");
}

global $db;
$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$adminName = $_SESSION['username'] ?? 'System'; 

if ($myPriv < 3) { die("Security Protocol: Unauthorized."); }

/** --- HILFSFUNKTION --- **/
if (!function_exists('getStandingText')) {
    function getStandingText($level) {
        $texts = [0 => "Good", 1 => "Warning I", 2 => "Warning II", 3 => "Restricted", 4 => "Suspended", 5 => "Banned"];
        return $texts[(int)$level] ?? "Unknown";
    }
}

/** --- AJAX HANDLER: SEARCH --- **/
if (isset($_POST['um_ajax_search'])) {
    $search = "%" . $_POST['um_ajax_search'] . "%";
    $filter = ($myPriv === 3) ? " AND priv_level <= 3" : "";
    $stmt = $db->prepare("SELECT id, username, standing FROM users WHERE username LIKE ? $filter LIMIT 8");
    $stmt->execute([$search]);
    while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $color = ($u['standing'] >= 3) ? 'var(--error-red)' : 'var(--glow-blue)';
        echo "<div class='result-item' style='border-left: 3px solid $color' onclick='loadUserEditor({$u['id']})'><strong>".htmlspecialchars($u['username'])."</strong></div>";
    }
    exit;
}

/** --- AJAX HANDLER: LIST CATEGORIES --- **/
if (isset($_POST['um_load_cat'])) {
    $cat = $_POST['um_load_cat'];
    $where = "1=1";
    if($cat == 'restricted') $where = "standing >= 3";
    elseif($cat == 'warned') $where = "standing BETWEEN 1 AND 2";
    elseif($cat == 'staff')  $where = "priv_level >= 3";
    if ($myPriv === 3) { $where .= " AND priv_level <= 3"; }

    $stmt = $db->query("SELECT id, username, standing, priv_level, forum_posts FROM users WHERE $where ORDER BY id DESC LIMIT 50");
    echo "<table class='um-table'><thead><tr><th>User</th><th>Standing</th><th>BS</th><th>Posts</th><th>Action</th></tr></thead><tbody>";
    while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row_style = ($u['standing'] >= 3) ? 'color: var(--error-red);' : '';
        echo "<tr style='$row_style'><td>".htmlspecialchars($u['username'])."</td><td>".getStandingText($u['standing'])."</td><td>".(int)$u['priv_level']."</td><td>".(int)($u['forum_posts'] ?? 0)."</td><td><button onclick='loadUserEditor(".$u['id'].")' class='btn-nexus-edit'>EDIT</button></td></tr>";
    }
    echo "</tbody></table>";
    exit;
}

/** --- AJAX HANDLER: LOAD EDITOR --- **/
if (isset($_POST['um_ajax_get_editor'])) {
    $id = (int)$_POST['um_ajax_get_editor'];
    $stmt = $db->prepare("SELECT u.*, a.PrivLevel as ingame_priv FROM users u LEFT JOIN account a ON u.username = a.Name WHERE u.id = ?");
    $stmt->execute([$id]);
    $u_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u_data) { 
        if ($myPriv === 3 && (int)$u_data['priv_level'] > 3) { die("Access Denied."); }
        $can_edit = true;
        include(__DIR__ . '/um_editor_view.php'); 
    }
    exit;
}

/** --- AJAX HANDLER: GET ADD FORM --- **/
if (isset($_POST['um_ajax_get_add_form'])) {
    if ($myPriv < 4) { die("Restricted."); }
    $can_edit = true; 
    $add_view = __DIR__ . '/um_add_user_view.php';
    if (file_exists($add_view)) { 
        include($add_view); 
    } else {
        echo "Template missing: um_add_user_view.php";
    }
    exit;
}

/** --- AJAX HANDLER: DELETE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'delete_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $target_id = (int)$_POST['target_id'];
    
    $stmt = $db->prepare("SELECT username, priv_level FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $u_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($u_data) {
        if ($myPriv == 4 && (int)$u_data['priv_level'] >= 5) { die("Cannot delete Super-Admins."); }
        
        $db->beginTransaction();
        $db->prepare("DELETE FROM account WHERE Name = ?")->execute([$u_data['username']]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
        $db->commit();
        echo "SUCCESS";
    }
    exit;
}

/** --- FORM SUBMIT: CREATE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'create_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $u_name = trim($_POST['u_name']);
    $u_pass = $_POST['u_pass'];
    
    $hash = aldhran_hash($u_pass);
    $db->prepare("INSERT INTO users (username, email, password, priv_level, standing, is_verified) VALUES (?, ?, ?, ?, 0, 1)")
       ->execute([$u_name, trim($_POST['u_email']), $hash, (int)$_POST['u_priv']]);
    
    $res = ""; for ($i = 0; $i < strlen($u_pass); $i++) { $res .= chr(0) . $u_pass[$i]; }
    $dol_hash = "##" . strtoupper(md5($res));
    $db->prepare("INSERT INTO account (Name, Password, Status, PrivLevel, CreationDate) VALUES (?, ?, 1, ?, NOW())")
       ->execute([$u_name, $dol_hash, (int)$_POST['u_priv']]);
    
    echo "SUCCESS"; exit;
}

/** --- FORM SUBMIT: UPDATE FULL --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'update_full') {
    $target_id = (int)$_POST['target_id'];
    $stmt = $db->prepare("SELECT username, avatar_url FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        if (!empty($_POST['u_new_password'])) {
            $new_pw = $_POST['u_new_password'];
            $hash = aldhran_hash($new_pw);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $target_id]);
            
            $res = ""; for ($i = 0; $i < strlen($new_pw); $i++) { $res .= chr(0) . $new_pw[$i]; }
            $dol_hash = "##" . strtoupper(md5($res));
            $db->prepare("UPDATE account SET Password = ? WHERE Name = ?")->execute([$dol_hash, $current['username']]);
        }
        
        // Avatar & Rest-Update
        $avatar_url = $current['avatar_url'];
        if (isset($_FILES['u_avatar']) && $_FILES['u_avatar']['error'] === UPLOAD_ERR_OK) {
            $newFile = 'avatar_' . $target_id . '_' . time() . '.' . pathinfo($_FILES['u_avatar']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['u_avatar']['tmp_name'], '../assets/img/avatars/' . $newFile)) {
                $avatar_url = 'assets/img/avatars/' . $newFile;
            }
        }
        
        $sql = "UPDATE users SET priv_level = ?, standing = ?, standing_reason = ?, biography = ?, user_title = ?, forum_posts = ?, forum_signature = ?, avatar_url = ? WHERE id = ?";
        $db->prepare($sql)->execute([ (int)$_POST['u_priv'], (int)$_POST['u_stand'], $_POST['u_reason'], $_POST['u_bio'], $_POST['u_title'], (int)$_POST['forum_posts'], $_POST['forum_signature'], $avatar_url, $target_id]);
        
        echo "SUCCESS";
    }
    exit;
}