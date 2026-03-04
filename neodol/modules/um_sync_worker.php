<?php
/**
 * UM SYNC WORKER - NeoDOL Standalone
 * Version: 2.3.0 - REMOVED DOL BRIDGE & MD5
 */

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$db_path = dirname(__DIR__) . '/includes/db.php';
if (file_exists($db_path)) {
    require_once($db_path);
} else {
    die("Nexus Core Error: File not found.");
}

global $db;
$myPriv = (int)($_SESSION['priv_level'] ?? 0);

if ($myPriv < 3) { die("Security Protocol: Unauthorized."); }

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
    // Keine Query mehr gegen 'account' Tabelle
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u_data) { 
        if ($myPriv === 3 && (int)$u_data['priv_level'] > 3) { die("Access Denied."); }
        $can_edit = true;
        include(__DIR__ . '/um_editor_view.php'); 
    }
    exit;
}

/** --- AJAX HANDLER: DELETE USER --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'delete_user') {
    if ($myPriv < 4) { die("Restricted."); }
    $target_id = (int)$_POST['target_id'];
    
    $stmt = $db->prepare("SELECT priv_level FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $u_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($u_data) {
        if ($myPriv == 4 && (int)$u_data['priv_level'] >= 5) { die("Cannot delete Super-Admins."); }
        
        // Löscht nur noch aus NeoDOL
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
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
    // Erstellt User in NeoDOL, is_verified auf 1 (da Admin-Action)
    $db->prepare("INSERT INTO users (username, email, password, priv_level, standing, is_verified) VALUES (?, ?, ?, ?, 0, 1)")
       ->execute([$u_name, trim($_POST['u_email']), $hash, (int)$_POST['u_priv']]);
    
    echo "SUCCESS"; exit;
}

/** --- FORM SUBMIT: UPDATE FULL --- **/
if (isset($_POST['um_action']) && $_POST['um_action'] === 'update_full') {
    $target_id = (int)$_POST['target_id'];
    $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        // Passwort Update (Nur Aldhran-Pepper Hash)
        if (!empty($_POST['u_new_password'])) {
            $hash = aldhran_hash($_POST['u_new_password']);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $target_id]);
        }
        
        // Avatar Update
        $avatar_url = $current['avatar_url'];
        if (isset($_FILES['u_avatar']) && $_FILES['u_avatar']['error'] === UPLOAD_ERR_OK) {
            $newFile = 'avatar_' . $target_id . '_' . time() . '.' . pathinfo($_FILES['u_avatar']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['u_avatar']['tmp_name'], '../assets/img/avatars/' . $newFile)) {
                $avatar_url = 'assets/img/avatars/' . $newFile;
            }
        }
        
        // Vollständiges Update ohne DOL-Felder
        $sql = "UPDATE users SET priv_level = ?, standing = ?, standing_reason = ?, biography = ?, user_title = ?, forum_posts = ?, forum_signature = ?, avatar_url = ? WHERE id = ?";
        $db->prepare($sql)->execute([ (int)$_POST['u_priv'], (int)$_POST['u_stand'], $_POST['u_reason'], $_POST['u_bio'], $_POST['u_title'], (int)$_POST['forum_posts'], $_POST['forum_signature'], $avatar_url, $target_id]);
        
        echo "SUCCESS";
    }
    exit;
}