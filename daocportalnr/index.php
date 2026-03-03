<?php
/**
 * DAoC Portal NR - Server Status Page
 * Version: 2.1.2 - Fixed Ghost Online Status & Timeout
 */
require_once('../includes/db.php'); 
if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

$user_ip = $_SERVER['REMOTE_ADDR'];
$launcher_ready = false;
$check = $db->prepare("SELECT last_ping FROM launcher_ips WHERE ip_address = ? AND last_ping > NOW() - INTERVAL 2 MINUTE");
$check->execute([$user_ip]);
if ($check->fetch()) { $launcher_ready = true; }

/**
 * Optimierter Server-Check
 */
function checkServer($ip, $port) {
    // Verhindert, dass lokale Test-IPs (127.x.x.x) fälschlicherweise als Online 
    // angezeigt werden, nur weil der Webserver auf sich selbst antwortet.
    if (strpos($ip, '127.') === 0 || $ip === 'localhost') {
        return false; 
    }

    $errno = 0;
    $errstr = "";
    // Timeout auf 0.3s erhöht für stabilere Abfragen bei echten Remote-Servern
    $fp = @fsockopen($ip, $port, $errno, $errstr, 0.3); 
    if ($fp) { 
        fclose($fp); 
        return true; 
    } 
    return false;
}

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
        .portal-header .subtitle { font-size: 10px; color: #444; margin-top: 8px; letter-spacing: 3px; text-transform: uppercase; }
        .account-box { background: #0d0d0d; padding: 15px; border-bottom: 1px solid #1a1a1a; display: flex; justify-content: center; gap: 20px; align-items: center; }
        .account-box label { font-family: 'Cinzel'; font-size: 10px; color: #555; }
        .account-box input { background: #000; border: 1px solid #222; color: #c5a059; padding: 6px 10px; font-family: 'Roboto Mono'; font-size: 12px; }
        .launcher-notice { max-width: 1000px; margin: 15px auto; padding: 10px; text-align: center; font-family: 'Cinzel'; font-size: 11px; border: 1px solid #222; }
        .launcher-ready { background: rgba(0, 255, 0, 0.03); color: #00ff00; border-color: rgba(0, 255, 0, 0.15); }
        .launcher-missing { background: rgba(197, 160, 89, 0.03); color: #c5a059; border-color: rgba(197, 160, 89, 0.2); }
        .server-grid { display: grid; grid-template-columns: 1fr; background: #1a1a1a; }
        .server-row { display: grid; grid-template-columns: 2fr 100px 80px 80px 120px 120px; background: #0c0c0c; padding: 18px 25px; align-items: center; transition: 0.2s; border-bottom: 1px solid #151515; }
        .server-row:hover { background: #111; box-shadow: inset 4px 0 0 #c5a059; }
        .header-row { background: #161616; font-size: 0.7em; color: #555; letter-spacing: 2px; font-weight: bold; text-transform: uppercase; }
        .srv-name a { color: #c5a059; font-weight: bold; font-family: 'Cinzel', serif; font-size: 1.1em; letter-spacing: 1px; text-decoration: none; transition: 0.3s; }
        .srv-name a:hover { color: #fff; text-shadow: 0 0 10px #c5a059; }
        .val { font-family: 'Roboto Mono', monospace; color: #aaa; }
        .status-pill { padding: 5px; border-radius: 1px; font-size: 9px; font-weight: bold; text-align: center; letter-spacing: 1px; border: 1px solid #222; }
        .online { color: #00ff00; border-color: rgba(0, 255, 0, 0.2); }
        .offline { color: #ff4444; border-color: rgba(255, 0, 0, 0.2); }
        .btn-nexus { background: transparent; border: 1px solid #c5a059; color: #c5a059; padding: 8px 15px; font-family: 'Cinzel'; font-size: 10px; cursor: pointer; text-decoration: none; transition: 0.3s; text-align: center; }
        .btn-nexus:hover { background: #c5a059; color: #000; }
        .btn-connect { border-color: #00ff00; color: #00ff00; }
        .nrfooter { background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 20px; text-align: center; color: #444; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; max-width: 1000px; margin: 40px auto 0 auto; font-family: 'Cinzel', serif; }
    </style>
</head>
<body>

<div class="top-nav">
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
        <div class="subtitle">Nostalgic Revival Edition</div>
    </div>

    <div class="account-box">
        <label>Account</label>
        <input type="text" id="acc_name" value="<?php echo htmlspecialchars($account_name); ?>">
        <label>Password</label>
        <input type="password" id="acc_pass" value="<?php echo htmlspecialchars($account_pass); ?>">
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
                    <?php echo htmlspecialchars($srv['server_name']); ?>
                </a>
            </div>
            
            <div class="val"><?php echo (int)$srv['player_count']; ?></div>
            <div class="val"><?php echo htmlspecialchars($srv['xp_rate']); ?></div>
            <div class="val"><?php echo htmlspecialchars($srv['rp_rate']); ?></div>
            
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
                        '<?php echo htmlspecialchars($srv['client_version'] ?? '1.109'); ?>',
                        '<?php echo htmlspecialchars($srv['shard_name'] ?? 'Aldhran'); ?>',
                        '<?php echo htmlspecialchars($srv['client_zip_url'] ?? ''); ?>'
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
    &copy; <?php echo date("Y"); ?> DAOC PORTAL NR 1.0<br>
    <span style="font-size: 8px; color: #222; margin-top: 5px; display: block;">Authorized Access Only</span>
</div>

<script>
function launch(ip, port, version, shardName, clientZipUrl) {
    const user = document.getElementById('acc_name').value;
    const pass = document.getElementById('acc_pass').value;
    if (!user || !pass) { alert("Bitte Account und Passwort eingeben."); return; }
    const uri = "daocnr://" + ip + ":" + port + 
                "/" + encodeURIComponent(version) + 
                "/" + encodeURIComponent(shardName) + 
                "/" + encodeURIComponent(clientZipUrl) + 
                "/" + encodeURIComponent(user) + 
                "/" + encodeURIComponent(pass);
    window.location.href = uri;
}
</script>
</body>
</html>