<?php
/**
 * DAoC Portal NR - Server Status Page
 * Version: 2.1.5 - Integrated with NeoDOL Core
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. PFAD-FIX: Wir gehen eine Ebene höher zu includes/db.php
if (file_exists('../includes/db.php')) {
    require_once('../includes/db.php'); 
} else {
    die("Bridge Error: Database core not found.");
}

// 2. SESSION HANDLING
if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

// 3. LAUNCHER CHECK
$user_ip = $_SERVER['REMOTE_ADDR'];
$launcher_ready = false;
try {
    // Falls launcher_ips in der neuen DB existiert
    $check = $db->prepare("SELECT last_ping FROM launcher_ips WHERE ip_address = ? AND last_ping > NOW() - INTERVAL 2 MINUTE");
    $check->execute([$user_ip]);
    if ($check->fetch()) { $launcher_ready = true; }
} catch (Exception $e) {
    // Falls die Tabelle noch nicht existiert, silent fail
}

function checkServer($ip, $port) {
    if (strpos($ip, '127.') === 0 || $ip === 'localhost') return false;
    $errno = 0; $errstr = "";
    $fp = @fsockopen($ip, $port, $errno, $errstr, 0.3);
    if ($fp) { fclose($fp); return true; }
    return false;
}

// 4. PLAYER COUNT UPDATE
$onlineCount = 0;
try {
    // Live count aus der DOL-Tabelle 'account'
    $onlineCount = (int)$db->query("SELECT COUNT(*) FROM account WHERE LastLogin > NOW() - INTERVAL 10 MINUTE")->fetchColumn();
    // Update in der Portal-Tabelle
    $db->prepare("UPDATE daoc_servers SET player_count = ? WHERE is_active = 1")->execute([$onlineCount]);
} catch (Exception $e) {
    error_log("[DAoCPortalNR] player_count Error: " . $e->getMessage());
}

// 5. FETCH SERVERS
$stmt = $db->query("SELECT * FROM daoc_servers WHERE is_active = 1 ORDER BY server_name ASC");
$servers = $stmt->fetchAll();

$account_name = $_SESSION['portal_username'] ?? '';
$account_pass = $_SESSION['portal_password'] ?? '';
$is_logged_in = isset($_SESSION['portal_user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DAoC Portal NR - Live Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .top-nav { max-width: 1000px; margin: 0 auto 10px auto; display: flex; justify-content: flex-end; gap: 15px; }
        .nav-btn { background: transparent; border: 1px solid #333; color: #666; padding: 6px 15px; font-family: 'Cinzel'; font-size: 10px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { border-color: #c5a059; color: #c5a059; }
        .portal-wrapper { max-width: 1000px; margin: 0 auto; border: 1px solid #222; background: #0a0a0a; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        .portal-header { background: #111; padding: 30px; border-bottom: 2px solid #c5a059; text-align: center; position: relative; }
        .portal-header h1 { font-family: 'Cinzel', serif; color: #c5a059; margin: 0; letter-spacing: 4px; text-transform: uppercase; font-size: 2.2em; }
        .account-box { background: #0d0d0d; padding: 15px; border-bottom: 1px solid #1a1a1a; display: flex; justify-content: center; gap: 20px; align-items: center; }
        .account-box label { font-family: 'Cinzel'; font-size: 10px; color: #555; }
        .account-box input { background: #000; border: 1px solid #222; color: #c5a059; padding: 6px 10px; font-family: 'Roboto Mono'; font-size: 12px; }
        .launcher-notice { max-width: 1000px; margin: 15px auto; padding: 10px; text-align: center; font-family: 'Cinzel'; font-size: 11px; }
        .launcher-ready { color: #00ff00; }
        .launcher-missing { color: #c5a059; }
        .server-grid { display: grid; grid-template-columns: 1fr; background: #1a1a1a; }
        .server-row { display: grid; grid-template-columns: 2fr 100px 80px 80px 120px 120px; background: #0c0c0c; padding: 18px 25px; align-items: center; border-bottom: 1px solid #151515; }
        .header-row { background: #161616; font-size: 0.7em; color: #555; letter-spacing: 2px; text-transform: uppercase; }
        .srv-name a { color: #c5a059; font-weight: bold; font-family: 'Cinzel', serif; text-decoration: none; }
        .player-count { font-family: 'Roboto Mono', monospace; color: #aaa; transition: color 0.4s; }
        .player-count.updated { color: #c5a059; }
        .status-pill { padding: 5px; border-radius: 1px; font-size: 9px; font-weight: bold; text-align: center; border: 1px solid #222; }
        .online { color: #00ff00; border-color: rgba(0, 255, 0, 0.2); }
        .offline { color: #ff4444; border-color: rgba(255, 0, 0, 0.2); }
        .btn-nexus { background: transparent; border: 1px solid #c5a059; color: #c5a059; padding: 8px 15px; font-family: 'Cinzel'; font-size: 10px; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .btn-nexus:hover { background: #c5a059; color: #000; }
        .btn-connect { border-color: #00ff00; color: #00ff00; }
        .nrfooter { background: #0a0a0a; border-top: 2px solid #c5a059; padding: 20px; text-align: center; color: #444; font-size: 10px; letter-spacing: 2px; max-width: 1000px; margin: 40px auto 0 auto; font-family: 'Cinzel', serif; }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="portal_faq.php" class="nav-btn"><i class="fas fa-question-circle"></i> FAQ</a>
    <?php if($is_logged_in): ?>
        <a href="shard_manager.php" class="nav-btn active"><i class="fas fa-crown"></i> MY DASHBOARD</a>
    <?php endif; ?>
    <a href="server_add.php" class="nav-btn"><i class="fas fa-plus"></i> ADD SHARD</a>
    <?php if(!$is_logged_in): ?>
        <a href="portal_register.php" class="nav-btn"><i class="fas fa-link"></i> REGISTER</a>
        <a href="portal_login.php" class="nav-btn"><i class="fas fa-sign-in-alt"></i> LOGIN</a>
    <?php else: ?>
        <a href="portal_logout.php" class="nav-btn"><i class="fas fa-power-off"></i></a>
    <?php endif; ?>
</div>

<div class="portal-wrapper">
    <div class="portal-header">
        <h1>DAoC Portal <span style="color: #fff;">NR</span></h1>
    </div>

    <div class="account-box">
        <label>Account</label>
        <input type="text" id="acc_name" value="<?php echo h($account_name); ?>">
        <label>Password</label>
        <input type="password" id="acc_pass" value="<?php echo h($account_pass); ?>">
    </div>

    <div class="launcher-notice <?php echo $launcher_ready ? 'launcher-ready' : 'launcher-missing'; ?>">
        <?php if($launcher_ready): ?>
            <i class="fas fa-check"></i> LAUNCHER READY
        <?php else: ?>
            <i class="fas fa-times"></i> LAUNCHER MISSING - <a href="downloads/portalnr-launcher.zip" style="color:#c5a059">DOWNLOAD OR RUN IT</a>
        <?php endif; ?>
    </div>
    
    <div class="server-grid">
        <div class="server-row header-row">
            <div>Server Name</div><div>Players</div><div>XP</div><div>RP</div><div style="text-align: center;">Status</div><div style="text-align: center;">Action</div>
        </div>

        <?php foreach($servers as $srv): 
            $is_online = checkServer($srv['server_ip'], $srv['server_port']);
        ?>
        <div class="server-row">
            <div class="srv-name">
                <a href="server_display.php?id=<?php echo $srv['id']; ?>">
                    <?php echo h($srv['server_name']); ?>
                </a>
            </div>

            <div class="player-count" data-id="<?php echo $srv['id']; ?>">
                <?php echo (int)$srv['player_count']; ?>
            </div>

            <div class="val"><?php echo h($srv['xp_rate']); ?></div>
            <div class="val"><?php echo h($srv['rp_rate']); ?></div>
            
            <div style="display: flex; justify-content: center;">
                <div class="status-pill <?php echo $is_online ? 'online' : 'offline'; ?>">
                    <?php echo $is_online ? 'ONLINE' : 'OFFLINE'; ?>
                </div>
            </div>

            <div style="text-align: center;">
                <?php if($launcher_ready && $is_online): ?>
                    <a href="javascript:void(0)" 
                       onclick="launch(
                        '<?php echo $srv['server_ip']; ?>',
                        '<?php echo $srv['server_port']; ?>',
                        '<?php echo h($srv['client_version'] ?? '1.109'); ?>',
                        '<?php echo h($srv['shard_name'] ?? 'Aldhran'); ?>',
                        '<?php echo h($srv['client_zip_url'] ?? ''); ?>',
                        '<?php echo h($srv['client_zip_hash'] ?? ''); ?>'
                       )" 
                       class="btn-nexus btn-connect">CONNECT</a>
                <?php else: ?>
                    <a href="server_display.php?id=<?php echo $srv['id']; ?>" class="btn-nexus">DISPLAY</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="nrfooter">
    &copy; <?php echo date("Y"); ?> PORTAL NOSTALGIC REVIVAL 1.0<br>
</div>

<script>
function launch(ip, port, version, shardName, clientZipUrl, zipHash) {
    const user = document.getElementById('acc_name').value;
    const pass = document.getElementById('acc_pass').value;
    if (!user || !pass) { alert("Insert Account name and PW!"); return; }
    const uri = "daocnr://" + ip + ":" + port + 
                "/" + encodeURIComponent(version) + 
                "/" + encodeURIComponent(shardName) + 
                "/" + encodeURIComponent(clientZipUrl) +
                "/" + encodeURIComponent(zipHash) +
                "/" + encodeURIComponent(user) + 
                "/" + encodeURIComponent(pass);
    window.location.href = uri;
}

function refreshPlayerCounts() {
    fetch('player_counts.php')
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.player-count[data-id]').forEach(el => {
                const id = el.dataset.id;
                if (data[id] !== undefined) {
                    const newVal = parseInt(data[id]);
                    const oldVal = parseInt(el.textContent);
                    if (newVal !== oldVal) {
                        el.textContent = newVal;
                        el.classList.add('updated');
                        setTimeout(() => el.classList.remove('updated'), 1500);
                    }
                }
            });
        })
        .catch(() => {});
}

refreshPlayerCounts();
setInterval(refreshPlayerCounts, 30000);
</script>
</body>
</html>