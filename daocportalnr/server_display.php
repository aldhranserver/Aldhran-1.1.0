<?php
/**
 * DAoC Portal NR - Server Detail & Connect
 * Location: htdocs/daocportalnr/server_display.php
 * Version: 1.0.4 - Fixed Status Sync (Live Check)
 */
require_once('../includes/db.php');

$sid = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM daoc_servers WHERE id = ? AND is_active = 1");
$stmt->execute([$sid]);
$srv = $stmt->fetch();

if (!$srv) { 
    die("Server not found or inactive. <a href='index.php'>Back to list</a>"); 
}

// Live-Status Prüfung wie in der index.php
function checkServerLive($ip, $port) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 1);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

$is_online_live = checkServerLive($srv['server_ip'], $srv['server_port']);
$statusColor = ($is_online_live) ? '#4CAF50' : '#F44336';
$statusText = ($is_online_live) ? 'ONLINE' : 'OFFLINE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($srv['server_name']); ?> - Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .detail-window { max-width: 800px; margin: 0 auto; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 40px; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        .back-nav { display: inline-block; margin-bottom: 20px; color: #444; text-decoration: none; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; }
        .back-nav:hover { color: #c5a059; }
        h1 { font-family: 'Cinzel', serif; color: #c5a059; text-align: center; margin-bottom: 5px; font-size: 2.5em; }
        .status-badge { text-align: center; font-size: 10px; font-weight: bold; letter-spacing: 2px; color: <?php echo $statusColor; ?>; margin-bottom: 20px; text-transform: uppercase; }
        .server-meta { text-align: center; font-size: 13px; color: #888; margin-bottom: 30px; font-family: 'Cinzel'; border-bottom: 1px solid #111; padding-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin: 20px 0; text-align: center; }
        .stat-item { background: #000; padding: 15px; border: 1px solid #111; transition: 0.3s; }
        .stat-item:hover { border-color: #333; }
        .stat-label { display: block; font-size: 10px; color: #555; text-transform: uppercase; margin-bottom: 5px; }
        .stat-value { font-family: 'Cinzel'; color: #eee; font-size: 16px; }
        .description { background: #000; padding: 25px; border: 1px solid #111; line-height: 1.8; margin: 20px 0; white-space: pre-wrap; color: #aaa; font-size: 14px; border-left: 2px solid #c5a059; min-height: 100px; }
        .connect-box { text-align: center; margin-top: 40px; padding: 30px; border-top: 1px solid #222; }
        .btn-connect { background: transparent; border: 1px solid #c5a059; color: #c5a059; padding: 18px 50px; font-family: 'Cinzel'; font-weight: bold; text-decoration: none; display: inline-block; cursor: pointer; transition: 0.3s; font-size: 16px; }
        .btn-connect:hover { background: #c5a059; color: #000; box-shadow: 0 0 25px rgba(197, 160, 89, 0.4); }
        .btn-connect:active { transform: translateY(2px); }
        .web-link { color: #c5a059; text-decoration: none; font-size: 13px; border-bottom: 1px solid transparent; transition: 0.3s; }
        .web-link:hover { border-bottom: 1px solid #c5a059; }
        footer { text-align: center; margin-top: 30px; font-size: 10px; color: #333; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="detail-window">
        <a href="index.php" class="back-nav"><i class="fas fa-chevron-left"></i> Back to Server List</a>
        <h1><?php echo htmlspecialchars($srv['server_name']); ?></h1>
        <div class="status-badge">● <?php echo $statusText; ?></div>
        <div class="server-meta">
            <?php echo htmlspecialchars($srv['expansion'] ?? 'Classic'); ?> Expansion
            <?php if (!empty($srv['website_url'])): ?>
                &nbsp; | &nbsp; <a href="<?php echo htmlspecialchars($srv['website_url']); ?>" target="_blank" class="web-link">Official Website</a>
            <?php endif; ?>
        </div>
        <div class="stats-grid">
            <div class="stat-item"><span class="stat-label">Players Online</span><span class="stat-value"><?php echo (int)($srv['pop_count'] ?? 0); ?></span></div>
            <div class="stat-item"><span class="stat-label">Experience</span><span class="stat-value"><?php echo htmlspecialchars($srv['xp_rate'] ?? '1x'); ?></span></div>
            <div class="stat-item"><span class="stat-label">Realm Points</span><span class="stat-value"><?php echo htmlspecialchars($srv['rp_rate'] ?? '1x'); ?></span></div>
        </div>
        <div class="description"><?php echo !empty($srv['server_description']) ? htmlspecialchars($srv['server_description']) : "<i>The administrator has not provided a description yet.</i>"; ?></div>
        <div class="connect-box">
            <?php if ($is_online_live): ?>
                <button onclick="initConnect()" class="btn-connect"><i class="fas fa-bolt"></i> LAUNCH VIA PORTAL</button>
            <?php else: ?>
                <button disabled style="opacity: 0.2; cursor: not-allowed;" class="btn-connect">SERVER CURRENTLY OFFLINE</button>
            <?php endif; ?>
        </div>
    </div>
    <footer>&copy; <?php echo date("Y"); ?> Aldhran - DAoC Portal Nostalgic Revival</footer>
    <script>
    function initConnect() {
        let user = prompt("Enter your Shard Username:");
        if (!user) return;
        let pass = prompt("Enter your Shard Password:");
        if (!pass) return;
        let auth = btoa(user + ":" + pass);
        let targetUri = "daocnr://" + "<?php echo $srv['server_ip']; ?>:<?php echo $srv['server_port']; ?>/" + auth;
        let start = Date.now();
        window.location.href = targetUri;
        setTimeout(function() {
            if (Date.now() - start < 1500) {
                if (confirm("Launcher not detected! Would you like to download the DAoC Portal NR Package (Zip) now?")) {
                    window.location.href = "downloads/daoc_portal_nr.zip";
                }
            }
        }, 800);
    }
    </script>
</body>
</html>