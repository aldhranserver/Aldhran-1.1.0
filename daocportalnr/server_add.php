<?php
/**
 * DAoC Portal NR - Add Shard (With Rules & Anti-Spam)
 * Version: 1.2.2 - Custom T&C Integration
 */
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

if (!isset($_SESSION['portal_user_id'])) {
    header("Location: portal_login.php");
    exit;
}

$msg = "";
$error = "";

if (isset($_POST['add_shard'])) {
    $name = trim($_POST['s_name']);
    $ip   = trim($_POST['s_ip']);
    $port = (int)$_POST['s_port'];
    $desc = trim($_POST['s_desc']);
    $url  = trim($_POST['s_url']);
    $uid  = $_SESSION['portal_user_id'];

    $check = $db->prepare("SELECT id FROM daoc_servers WHERE server_ip = ? AND server_port = ?");
    $check->execute([$ip, $port]);

    if ($check->rowCount() > 0) {
        $error = "This server address is already registered in the portal.";
    } else {
        $stmt = $db->prepare("INSERT INTO daoc_servers (server_name, server_ip, server_port, server_description, website_url, owner_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 0)");
        if ($stmt->execute([$name, $ip, $port, $desc, $url, $uid])) {
            $msg = "Shard successfully submitted! It will be live after a brief review.";
        } else {
            $error = "Error saving data. Please check your inputs.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Shard - DAoC Portal NR</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .window { max-width: 650px; margin: 0 auto; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        h2 { font-family: 'Cinzel', serif; color: #c5a059; text-align: center; margin-top: 0; letter-spacing: 2px; }
        
        .rules-box { background: #000; border: 1px solid #1a1a1a; padding: 25px; margin-bottom: 25px; font-size: 13px; line-height: 1.6; color: #888; text-align: left; }
        .rules-box b { color: #c5a059; }
        
        input, textarea { width: 100%; padding: 12px; background: #050505; border: 1px solid #222; color: #fff; margin-bottom: 15px; box-sizing: border-box; outline: none; font-family: inherit; }
        input:focus, textarea:focus { border-color: #c5a059; }
        label { font-size: 10px; color: #555; letter-spacing: 1px; text-transform: uppercase; display: block; margin-bottom: 5px; }
        
        .btn-active { width: 100%; padding: 15px; background: transparent; border: 1px solid #c5a059; color: #c5a059; cursor: pointer; font-family: 'Cinzel'; font-weight: bold; transition: 0.3s; }
        .btn-active:disabled { border-color: #333; color: #333; cursor: not-allowed; }
        .btn-active:not(:disabled):hover { background: #c5a059; color: #000; }
        
        .alert { text-align: center; margin-bottom: 20px; padding: 10px; font-size: 13px; }
        .error { color: #ff4444; border: 1px solid #440000; background: rgba(255,0,0,0.05); }
        .success { color: #00ff00; border: 1px solid #004400; background: rgba(0,255,0,0.05); }
        
        #form-container { display: none; margin-top: 30px; border-top: 1px solid #222; padding-top: 30px; }
    </style>
</head>
<body>
    <div class="window">
        <h2>Shard Registration</h2>
        
        <?php if($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
        <?php if($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>

        <div id="rules-section">
            <div class="rules-box">
                <b>Terms & Conditions:</b><br><br>
                1. The <b>DAoC Portal NR Division</b> validates every entry before public release.<br>
                2. We generally approve all servers to allow every dev to showcase their project.<br>
                3. Shards with massive negative behavior (DOL Community etc.) will be warned, suspended, or deleted.<br>
                4. Final goal is a moderation staff consisting of <b>DOL Team Members</b> to remain unbiased. Until then, Aldhran staff will moderate.
            </div>
            <button id="timer-btn" class="btn-active" disabled>
                PLEASE READ... (60s)
            </button>
        </div>

        <div id="form-container">
            <form method="POST">
                <label>Shard Name</label>
                <input type="text" name="s_name" placeholder="e.g. Avalon Revival" required>
                
                <label>IP / Hostname</label>
                <input type="text" name="s_ip" placeholder="login.your-server.com" required>
                
                <label>Port</label>
                <input type="number" name="s_port" value="10300" required>

                <label>Website URL (Optional)</label>
                <input type="url" name="s_url" placeholder="https://www.your-shard.com">

                <label>Server Description</label>
                <textarea name="s_desc" rows="5" placeholder="Describe your world, custom features, etc..."></textarea>

                <button type="submit" name="add_shard" class="btn-active">COMPLETE REGISTRATION</button>
            </form>
        </div>
        
        <center style="margin-top: 25px;">
            <a href="shard_manager.php" style="color: #444; text-decoration: none; font-size: 11px; font-family: 'Cinzel';">&laquo; BACK TO MANAGER</a>
        </center>
    </div>

    <script>
        let timeLeft = 60;
        const btn = document.getElementById('timer-btn');
        const rules = document.getElementById('rules-section');
        const form = document.getElementById('form-container');

        const countdown = setInterval(() => {
            timeLeft--;
            btn.innerHTML = `PLEASE READ... (${timeLeft}s)`;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                btn.innerHTML = "RULES READ & ACCEPTED";
                btn.disabled = false;
            }
        }, 1000);

        btn.onclick = () => {
            rules.style.display = 'none';
            form.style.display = 'block';
        };
    </script>
</body>
</html>