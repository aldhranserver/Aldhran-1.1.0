<?php
/**
 * DAoC Portal NR - Edit Shard
 * Version: 1.2.0 - Multi-Shard Launcher Support
 */
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['portal_user_id'])) { header("Location: portal_login.php"); exit; }

$uid = (int)$_SESSION['portal_user_id'];
$sid = (int)$_GET['id'];
$msg = "";

$stmt = $db->prepare("SELECT * FROM daoc_servers WHERE id = ? AND owner_id = ?");
$stmt->execute([$sid, $uid]);
$shard = $stmt->fetch();

if (!$shard) { die("Access Denied: Shard not found or ownership mismatch."); }

if (isset($_POST['update_shard'])) {
    $name    = trim($_POST['s_name']);
    $ip      = trim($_POST['s_ip']);
    $port    = (int)$_POST['s_port'];
    $desc    = trim($_POST['s_desc']);
    $url     = trim($_POST['s_url']);
    $s_short = trim($_POST['s_shard_name']);
    $s_zip   = trim($_POST['s_zip_url']);

    $update = $db->prepare("UPDATE daoc_servers SET server_name = ?, server_ip = ?, server_port = ?, server_description = ?, website_url = ?, shard_name = ?, client_zip_url = ? WHERE id = ? AND owner_id = ?");
    if ($update->execute([$name, $ip, $port, $desc, $url, $s_short, $s_zip, $sid, $uid])) {
        $msg = "Shard updated successfully!";
        // Daten für Anzeige aktualisieren
        $shard['server_name'] = $name; $shard['server_ip'] = $ip; $shard['server_port'] = $port;
        $shard['server_description'] = $desc; $shard['website_url'] = $url;
        $shard['shard_name'] = $s_short; $shard['client_zip_url'] = $s_zip;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Shard - DAoC Portal NR</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .window { max-width: 600px; margin: 0 auto; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        h2 { font-family: 'Cinzel', serif; color: #c5a059; text-align: center; margin-top: 0; }
        input, textarea { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; margin-bottom: 15px; box-sizing: border-box; outline: none; font-family: inherit; }
        input:focus, textarea:focus { border-color: #c5a059; }
        label { font-size: 10px; color: #555; letter-spacing: 1px; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .btn-active { width: 100%; padding: 15px; background: transparent; border: 1px solid #c5a059; color: #c5a059; cursor: pointer; font-family: 'Cinzel'; font-weight: bold; }
        .btn-active:hover { background: #c5a059; color: #000; }
        .msg { color: #00ff00; text-align: center; margin-bottom: 15px; font-size: 14px; }
        .field-info { font-size: 9px; color: #444; margin-top: -12px; margin-bottom: 15px; font-style: italic; }
    </style>
</head>
<body>
    <div class="window">
        <h2>Edit Shard</h2>
        <?php if($msg): ?><div class="msg"><?php echo $msg; ?></div><?php endif; ?>
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
            <input type="url" name="s_zip_url" value="<?php echo htmlspecialchars($shard['client_zip_url'] ?? ''); ?>" required>
            <div class="field-info">Must be a direct link to a ZIP containing the game.dll.</div>

            <label>Website URL (Optional)</label>
            <input type="url" name="s_url" value="<?php echo htmlspecialchars($shard['website_url'] ?? ''); ?>">

            <label>Server Description</label>
            <textarea name="s_desc" rows="5"><?php echo htmlspecialchars($shard['server_description'] ?? ''); ?></textarea>

            <button type="submit" name="update_shard" class="btn-active">SAVE CHANGES</button>
        </form>
        <center style="margin-top: 20px;"><a href="shard_manager.php" style="color: #666; text-decoration: none; font-size: 11px; font-family: 'Cinzel';">&laquo; BACK TO MANAGER</a></center>
    </div>
</body>
</html>