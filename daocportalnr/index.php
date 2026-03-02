<?php
/**
 * DAoC Portal NR - Server Status Page
 * Location: htdocs/daocportalnr/index.php
 * Version: 1.5.0 - Handshake Integration
 */

require_once('../includes/db.php'); 

if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

$is_logged_in = isset($_SESSION['portal_user_id']);

// --- NEU: HANDSHAKE PRÜFUNG ---
// Wir prüfen, ob der Launcher innerhalb der letzten 10 Minuten einen Ping gesendet hat
$launcher_timeout = 600; // 10 Minuten
$launcher_ready = false;

if (isset($_SESSION['launcher_present']) && $_SESSION['launcher_present'] === true) {
    if (isset($_SESSION['last_ping_time']) && (time() - $_SESSION['last_ping_time']) < $launcher_timeout) {
        $launcher_ready = true;
    } else {
        // Timeout abgelaufen - Status zurücksetzen
        $_SESSION['launcher_present'] = false;
    }
}
// ------------------------------

function checkServer($ip, $port) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 0.5); 
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

try {
    $stmt = $db->query("SELECT * FROM daoc_servers WHERE is_active = 1 ORDER BY server_name ASC");
    $servers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
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
        .portal-wrapper { max-width: 1000px; margin: 20px auto; border: 1px solid #222; background: #0a0a0a; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        .portal-header { background: #111; padding: 25px; border-bottom: 2px solid #c5a059; text-align: center; }
        .portal-header h1 { font-family: 'Cinzel', serif; color: #c5a059; margin: 0; letter-spacing: 3px; text-transform: uppercase; }
        
        /* Neuer Launcher Status Balken */
        .launcher-notice { 
            max-width: 1000px; margin: 0 auto 15px auto; padding: 10px; 
            text-align: center; font-family: 'Cinzel'; font-size: 11px;
            border: 1px solid #222;
        }
        .launcher-ready { background: rgba(0, 255, 0, 0.05); color: #00ff00; border-color: rgba(0, 255, 0, 0.2); }
        .launcher-missing { background: rgba(197, 160, 89, 0.05); color: #c5a059; border-color: #c5a059; }

        .server-grid { display: grid; grid-template-columns: 1fr; gap: 1px; background: #1a1a1a; }
        .server-row { display: grid; grid-template-columns: 2fr 100px 80px 80px 120px 120px; background: #0c0c0c; padding: 15px 20px; align-items: center; transition: 0.2s; }
        .server-row:hover { background: #151515; box-shadow: inset 5px 0 0 #c5a059; }
        .header-row { background: #161616; font-size: 0.75em; color: #666; letter-spacing: 1px; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #222; }
        .val { font-family: 'Roboto Mono', monospace; color: #e0e0e0; font-size: 0.95em; }
        .srv-name a { color: #c5a059; font-weight: bold; font-family: 'Cinzel', serif; font-size: 1.1em; text-decoration: none; transition: 0.3s; }
        .srv-name a:hover { color: #fff; text-shadow: 0 0 10px #c5a059; }
        .status-pill { padding: 6px; border-radius: 2px; font-size: 10px; font-weight: bold; text-align: center; letter-spacing: 1px; text-transform: uppercase; }
        .online { background: rgba(0, 255, 0, 0.05); color: #00ff00; border: 1px solid rgba(0, 255, 0, 0.2); }
        .offline { background: rgba(255, 0, 0, 0.05); color: #ff4444; border: 1px solid rgba(255, 0, 0, 0.2); }
        .btn-nexus { background: rgba(197, 160, 89, 0.1); border: 1px solid #c5a059; color: #c5a059; padding: 8px 15px; font-family: 'Cinzel'; font-size: 10px; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; text-align: center; }
        .btn-nexus:hover { background: #c5a059; color: #000; }
        .btn-logout { border-color: #ff4444; color: #ff4444; }
        .nrfooter { background: #111; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 12px; text-align: center; color: #777; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; max-width: 1000px; margin: 30px auto 0 auto; font-family: 'Cinzel', serif; }
    </style>
</head>
<body>

<?php if($launcher_ready): ?>
    <div class="launcher-notice launcher-ready">
        <i class="fas fa-check-circle"></i> Launcher Detected & Active - Shards are ready for transport.
    </div>
<?php else: ?>
    <div class="launcher-notice launcher-missing">
        <i class="fas fa-exclamation-triangle"></i> Launcher not detected. 
        <a href="download/DAoCPortalNR.zip" style="color: #fff; text-decoration: underline; margin-left: 10px;">Download Launcher</a> 
        to enable direct connect.
    </div>
<?php endif; ?>

<div style="max-width: 1000px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center;">
    <div style="font-family: 'Cinzel'; font-size: 12px; color: #444;">
        <?php if($is_logged_in): ?>
            Logged in as: <span style="color: #c5a059;"><?php echo htmlspecialchars($_SESSION['portal_username']); ?></span>
        <?php endif; ?>
    </div>
    <div>
        <?php if($is_logged_in): ?>
            <a href="shard_manager.php" class="btn-nexus"><i class="fas fa-tasks"></i> DASHBOARD</a>
            <a href="portal_logout.php" class="btn-nexus btn-logout"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        <?php else: ?>
            <a href="portal_login.php" class="btn-nexus"><i class="fas fa-sign-in-alt"></i> LOGIN</a>
            <a href="server_add.php" class="btn-nexus"><i class="fas fa-plus-circle"></i> ADD SHARD</a>
        <?php endif; ?>
    </div>
</div>

<div class="portal-wrapper">
    <div class="portal-header">
        <h1>DAoC Portal <span style="color: #fff;">NR</span></h1>
        <div style="font-size: 10px; color: #444; margin-top: 5px; letter-spacing: 2px;">NOSTALGIC REVIVAL EDITION</div>
    </div>
    
    <div class="server-grid">
        <div class="server-row header-row">
            <div>Server Name</div>
            <div>Players</div>
            <div>XP</div>
            <div>RP</div>
            <div style="text-align: center;">Status</div>
            <div style="text-align: center;">Action</div>
        </div>

        <?php if (empty($servers)): ?>
            <div class="server-row" style="grid-template-columns: 1fr; text-align: center; color: #444; font-style: italic; padding: 40px;">Currently no shards validated.</div>
        <?php else: ?>
            <?php foreach($servers as $srv): 
                $is_online = checkServer($srv['server_ip'], $srv['server_port']);
            ?>
            <div class="server-row">
                <div class="srv-name">
                    <a href="server_display.php?id=<?php echo $srv['id']; ?>">
                        <?php echo htmlspecialchars($srv['server_name']); ?>
                    </a>
                </div>

                <div class="val">
                    <i class="fas fa-users" style="color: #444; font-size: 0.8em;"></i> 
                    <?php echo (int)($srv['player_count'] ?? $srv['pop_count'] ?? 0); ?>
                </div>

                <div class="val" style="color: #888;"><?php echo htmlspecialchars($srv['xp_rate'] ?? '1x'); ?></div>
                <div class="val" style="color: #888;"><?php echo htmlspecialchars($srv['rp_rate'] ?? '1x'); ?></div>

                <div style="display: flex; justify-content: center;">
                    <?php if($is_online): ?>
                        <div class="status-pill online">ONLINE</div>
                    <?php else: ?>
                        <div class="status-pill offline">OFFLINE</div>
                    <?php endif; ?>
                </div>

                <div style="text-align: center;">
                    <?php if($launcher_ready && $is_online): ?>
                        <a href="daocnr://<?php echo $srv['server_ip']; ?>:<?php echo $srv['server_port']; ?>/<?php echo $_SESSION['portal_token'] ?? 'guest'; ?>" class="btn-nexus" style="width: 80px; background: rgba(0,255,0,0.1); border-color: #00ff00; color: #00ff00;">
                            CONNECT
                        </a>
                    <?php else: ?>
                        <a href="server_display.php?id=<?php echo $srv['id']; ?>" class="btn-nexus" style="width: 80px;">
                            DETAILS
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="nrfooter">&copy; <?php echo date("Y"); ?> DAoC Portal Nostalgic Revival - Refurbished by Aldhran</div>

</body>
</html>