<?php
/**
 * USER EDIT VIEW - Aldhran Freeshard
 * Version: 0.6.4 (Diagnose-Modus)
 */

// 1. Fehleranzeige erzwingen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Sicherheits-Check mit Feedback
if (!isset($_SESSION['user_id'])) {
    die("<div style='color:red; padding:20px;'>Fehler: Keine aktive Session. Bitte logge dich neu ein.</div>");
}

if ($_SESSION['priv_level'] < 4) {
    die("<div style='color:red; padding:20px;'>Fehler: Deine Berechtigungsstufe (Level ".$_SESSION['priv_level'].") reicht nicht aus.</div>");
}

// 3. Datenbank-Check
if (!isset($db)) {
    require_once('includes/db.php');
}

// 4. ID-Check
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div style='color:orange; padding:20px;'>Fehler: Keine User-ID in der URL gefunden (p=user_edit&id=XX fehlt).</div>");
}

$target_id = (int)$_GET['id'];
$res = $db->query("SELECT * FROM users WHERE id = $target_id");

if (!$res || $res->num_rows === 0) {
    die("<div style='color:orange; padding:20px;'>Fehler: User mit ID #$target_id existiert nicht in der Datenbank.</div>");
}

$udata = $res->fetch_assoc();
?>

<div class="admin-container" style="background: #0c0c0c; border: 1px solid #222; padding: 40px; border-top: 3px solid #d4af37; max-width: 600px; margin: 40px auto; font-family: Arial, sans-serif; box-shadow: 0 0 30px #000;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="color: #d4af37; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-family: 'Cinzel', serif;">Edit User Account</h2>
        <a href="index.php?p=permissions" style="color: #666; text-decoration: none; font-size: 11px; text-transform: uppercase; border: 1px solid #333; padding: 5px 10px;">Back to List</a>
    </div>

    <form method="POST" action="index.php?p=permissions">
        <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
        
        <div style="margin-bottom: 25px;">
            <label style="color:#555; display:block; font-size:10px; text-transform:uppercase; margin-bottom:8px; letter-spacing:1px;">Username</label>
            <input type="text" value="<?php echo htmlspecialchars($udata['username']); ?>" disabled style="width:100%; padding:14px; background:#050505; border:1px solid #222; color:#555; cursor:not-allowed;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="color:#555; display:block; font-size:10px; text-transform:uppercase; margin-bottom:8px; letter-spacing:1px;">User Title</label>
            <input type="text" name="user_title" value="<?php echo htmlspecialchars($udata['user_title'] ?? ''); ?>" placeholder="e.g. Guardian, Merchant..." style="width:100%; padding:14px; background:#000; border:1px solid #333; color:#fff;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
            <div>
                <label style="color:#555; display:block; font-size:10px; text-transform:uppercase; margin-bottom:8px; letter-spacing:1px;">Privilege Level</label>
                <select name="priv_level" style="width:100%; padding:14px; background:#000; border:1px solid #333; color:#fff;">
                    <option value="1" <?php echo ($udata['priv_level'] == 1) ? 'selected' : ''; ?>>Level 1 (User)</option>
                    <option value="2" <?php echo ($udata['priv_level'] == 2) ? 'selected' : ''; ?>>Level 2 (Support)</option>
                    <option value="3" <?php echo ($udata['priv_level'] == 3) ? 'selected' : ''; ?>>Level 3 (GM)</option>
                    <option value="4" <?php echo ($udata['priv_level'] == 4) ? 'selected' : ''; ?>>Level 4 (Admin)</option>
                </select>
            </div>
            <div>
                <label style="color:#555; display:block; font-size:10px; text-transform:uppercase; margin-bottom:8px; letter-spacing:1px;">Standing (0-4)</label>
                <input type="number" name="standing" min="0" max="4" value="<?php echo (int)$udata['standing']; ?>" style="width:100%; padding:14px; background:#000; border:1px solid #333; color:#fff;">
            </div>
        </div>

        <div style="margin-bottom: 30px; padding: 15px; background: rgba(212,175,55,0.05); border: 1px solid rgba(212,175,55,0.1);">
            <label style="color:#d4af37; cursor:pointer; font-size: 13px; display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_verified" <?php echo $udata['is_verified'] ? 'checked' : ''; ?>> 
                <span>Account Email Verified</span>
            </label>
        </div>

        <button type="submit" name="update_user" style="width:100%; padding:16px; background:#d4af37; border:none; color:#000; font-weight:bold; cursor:pointer; text-transform:uppercase; letter-spacing:2px; transition: 0.3s;">Save Account Changes</button>
    </form>

    <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #222;">
        <h3 style="color:#ff4444; font-size:11px; text-transform:uppercase; margin-bottom:15px; letter-spacing:1px;">Reset User Password</h3>
        <form method="POST" action="index.php?p=permissions">
            <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
            <div style="display: flex; gap: 10px;">
                <input type="password" name="new_password" placeholder="Enter new password" required style="flex:2; padding:12px; background:#000; border:1px solid #333; color:#fff;">
                <button type="submit" name="change_password_admin" style="flex:1; background:transparent; border:1px solid #ff4444; color:#ff4444; font-weight:bold; cursor:pointer; font-size:11px; text-transform:uppercase; transition: 0.3s;">Update</button>
            </div>
        </form>
    </div>
</div>