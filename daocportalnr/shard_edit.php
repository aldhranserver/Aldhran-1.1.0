<?php
/**
 * DAoC Portal NR - Edit Shard
 * Version: 1.3.0 - Auto Hash Refresh on ZIP URL Change + Manual Refresh Button
 */
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['portal_user_id'])) { header("Location: portal_login.php"); exit; }

$uid = (int)$_SESSION['portal_user_id'];
$sid = (int)$_GET['id'];
$msg = "";
$msg_type = "success";

$stmt = $db->prepare("SELECT * FROM daoc_servers WHERE id = ? AND owner_id = ?");
$stmt->execute([$sid, $uid]);
$shard = $stmt->fetch();

if (!$shard) { die("Access Denied: Shard not found or ownership mismatch."); }

// ── Manual Hash Refresh ─────────────────────────────────────────────────────
if (isset($_POST['refresh_hash'])) {
    $zip_url = $shard['client_zip_url'];
    if (!empty($zip_url)) {
        $zip_data = @file_get_contents($zip_url);
        if ($zip_data !== false) {
            $new_hash = md5($zip_data);
            $db->prepare("UPDATE daoc_servers SET client_zip_hash = ? WHERE id = ? AND owner_id = ?")
               ->execute([$new_hash, $sid, $uid]);
            $shard['client_zip_hash'] = $new_hash;
            $msg = "Hash updated: <code style='color:#fff'>" . $new_hash . "</code><br>All players will automatically re-download on their next Connect.";
        } else {
            $msg = "ZIP could not be reached. Please check the ZIP URL.";
            $msg_type = "error";
        }
    } else {
        $msg = "No ZIP URL configured.";
        $msg_type = "error";
    }
}

// ── Save Shard ──────────────────────────────────────────────────────────────
if (isset($_POST['update_shard'])) {
    $name    = trim($_POST['s_name']);
    $ip      = trim($_POST['s_ip']);
    $port    = (int)$_POST['s_port'];
    $desc    = trim($_POST['s_desc']);
    $url     = trim($_POST['s_url']);
    $s_short = trim($_POST['s_shard_name']);
    $s_zip   = trim($_POST['s_zip_url']);
    $s_ver   = trim($_POST['s_version']);

    // ZIP URL changed? → Automatically recompute hash
    $new_hash = $shard['client_zip_hash'];
    $zip_url_changed = ($s_zip !== $shard['client_zip_url']);

    if ($zip_url_changed && !empty($s_zip)) {
        $zip_data = @file_get_contents($s_zip);
        if ($zip_data !== false) {
            $new_hash = md5($zip_data);
            $msg = "ZIP URL changed – hash automatically updated. Players will re-download on their next Connect.";
        } else {
            $msg = "Saved, but ZIP URL could not be reached – hash was not updated.";
            $msg_type = "error";
        }
    }

    $update = $db->prepare("UPDATE daoc_servers SET server_name = ?, server_ip = ?, server_port = ?, server_description = ?, website_url = ?, shard_name = ?, client_zip_url = ?, client_version = ?, client_zip_hash = ? WHERE id = ? AND owner_id = ?");
    if ($update->execute([$name, $ip, $port, $desc, $url, $s_short, $s_zip, $s_ver, $new_hash, $sid, $uid])) {
        if (empty($msg)) $msg = "Shard updated successfully!";
        $shard['server_name']        = $name;
        $shard['server_ip']          = $ip;
        $shard['server_port']        = $port;
        $shard['server_description'] = $desc;
        $shard['website_url']        = $url;
        $shard['shard_name']         = $s_short;
        $shard['client_zip_url']     = $s_zip;
        $shard['client_version']     = $s_ver;
        $shard['client_zip_hash']    = $new_hash;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Shard - DAoC Portal NR</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .window { max-width: 600px; margin: 0 auto; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        h2 { font-family: 'Cinzel', serif; color: #c5a059; text-align: center; margin-top: 0; }
        input, textarea { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; margin-bottom: 15px; box-sizing: border-box; outline: none; font-family: inherit; }
        input:focus, textarea:focus { border-color: #c5a059; }
        label { font-size: 10px; color: #555; letter-spacing: 1px; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .btn-active { width: 100%; padding: 15px; background: transparent; border: 1px solid #c5a059; color: #c5a059; cursor: pointer; font-family: 'Cinzel'; font-weight: bold; transition: 0.3s; }
        .btn-active:hover { background: #c5a059; color: #000; }
        .btn-refresh { width: 100%; padding: 12px; background: transparent; border: 1px solid #3a6fa8; color: #3a8fd8; cursor: pointer; font-family: 'Cinzel'; font-size: 11px; font-weight: bold; transition: 0.3s; margin-bottom: 25px; }
        .btn-refresh:hover { background: #3a6fa8; color: #fff; }
        .msg { text-align: center; margin-bottom: 15px; font-size: 13px; padding: 10px; border: 1px solid; }
        .msg.success { color: #00ff00; border-color: rgba(0,255,0,0.2); background: rgba(0,255,0,0.03); }
        .msg.error   { color: #ff4444; border-color: rgba(255,0,0,0.2); background: rgba(255,0,0,0.03); }
        .field-info { font-size: 9px; color: #444; margin-top: -12px; margin-bottom: 15px; font-style: italic; }
        .hash-display { font-family: 'Roboto Mono', monospace; font-size: 10px; color: #3a8fd8; background: #000; border: 1px solid #111; padding: 8px 12px; margin-bottom: 15px; word-break: break-all; }
        .hash-label { font-size: 9px; color: #444; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .divider { border: none; border-top: 1px solid #1a1a1a; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="window">
        <h2>Edit Shard</h2>

        <?php if($msg): ?>
            <div class="msg <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Hash Status & Refresh -->
        <div class="hash-label"><i class="fas fa-fingerprint"></i> Current ZIP Hash</div>
        <div class="hash-display">
            <?php echo !empty($shard['client_zip_hash']) ? $shard['client_zip_hash'] : '— no hash stored —'; ?>
        </div>
        <form method="POST">
            <button type="submit" name="refresh_hash" class="btn-refresh"
                onclick="return confirm('Re-fetch ZIP and update hash now?\nAll players will re-download on their next Connect.')">
                ↻ &nbsp;REFRESH HASH (Re-read ZIP)
            </button>
        </form>

        <hr class="divider">

        <!-- Edit Shard -->
        <form method="POST">
            <label>Shard Name (Public)</label>
            <input type="text" name="s_name" value="<?php echo htmlspecialchars($shard['server_name']); ?>" required>

            <label>Shard Identifier (Technical Name / Folder)</label>
            <input type="text" name="s_shard_name" value="<?php echo htmlspecialchars($shard['shard_name'] ?? ''); ?>" required pattern="[a-zA-Z0-9_-]+" oninput="this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '')">
            <div class="field-info">Single word, no spaces. Determines the local folder name.</div>

            <label>IP / Hostname</label>
            <input type="text" name="s_ip" value="<?php echo htmlspecialchars($shard['server_ip']); ?>" required>

            <label>Port</label>
            <input type="number" name="s_port" value="<?php echo (int)$shard['server_port']; ?>" required>

            <label>Client ZIP URL</label>
            <input type="url" name="s_zip_url" id="zip_url" value="<?php echo htmlspecialchars($shard['client_zip_url'] ?? ''); ?>" required>
            <div class="field-info">Must be a direct link to a ZIP containing game.dll. <b style="color:#c5a059">Hash is automatically updated when the URL is changed.</b></div>

            <label>Required Client Version</label>
            <input type="text" name="s_version" value="<?php echo htmlspecialchars($shard['client_version'] ?? '1.109'); ?>" placeholder="e.g. 1.109" required>
            <div class="field-info">The version string used by the Launcher to verify the local files.</div>

            <label>Website URL (Optional)</label>
            <input type="url" name="s_url" value="<?php echo htmlspecialchars($shard['website_url'] ?? ''); ?>">

            <label>Server Description</label>
            <textarea name="s_desc" rows="5"><?php echo htmlspecialchars($shard['server_description'] ?? ''); ?></textarea>

            <button type="submit" name="update_shard" class="btn-active">SAVE CHANGES</button>
        </form>

        <center style="margin-top: 20px;">
            <a href="shard_manager.php" style="color: #666; text-decoration: none; font-size: 11px; font-family: 'Cinzel';">&laquo; BACK TO MANAGER</a>
        </center>
    </div>
</body>
</html>
