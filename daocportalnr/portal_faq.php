<?php
/**
 * DAoC Portal NR - FAQ Page
 * Version: 1.1.0 - English Version with Virtual File Logic
 */
require_once('../includes/db.php'); 
if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}
$is_logged_in = isset($_SESSION['portal_user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portal NR - FAQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .top-nav { max-width: 1000px; margin: 0 auto 10px auto; display: flex; justify-content: flex-end; gap: 15px; }
        .nav-btn { background: transparent; border: 1px solid #333; color: #666; padding: 6px 15px; font-family: 'Cinzel'; font-size: 10px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { border-color: #c5a059; color: #c5a059; }
        .portal-wrapper { max-width: 800px; margin: 0 auto; border: 1px solid #222; background: #0a0a0a; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        .portal-header { background: #111; padding: 30px; border-bottom: 2px solid #c5a059; text-align: center; }
        .portal-header h1 { font-family: 'Cinzel', serif; color: #c5a059; margin: 0; letter-spacing: 4px; text-transform: uppercase; font-size: 2.2em; }
        .faq-content { padding: 40px; }
        .faq-item { margin-bottom: 30px; border-bottom: 1px solid #1a1a1a; padding-bottom: 20px; }
        .faq-question { font-family: 'Cinzel', serif; color: #c5a059; font-size: 1.2em; margin-bottom: 10px; display: block; }
        .faq-answer { line-height: 1.6; color: #aaa; font-size: 0.95em; }
        .highlight { color: #fff; font-weight: bold; }
        .nrfooter { background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 20px; text-align: center; color: #444; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; max-width: 800px; margin: 40px auto 0 auto; font-family: 'Cinzel', serif; }
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="nav-btn"><i class="fas fa-home"></i> BACK TO PORTAL</a>
</div>

<div class="portal-wrapper">
    <div class="portal-header">
        <h1>Frequently Asked <span style="color: #fff;">Questions</span></h1>
    </div>

    <div class="faq-content">
        
        <div class="faq-item">
            <span class="faq-question">What is Portal NR?</span>
            <div class="faq-answer">
                <span class="highlight">Portal NR (Nostalgic Revival)</span> is a modern platform designed to unify classic DAoC shards. Unlike the legacy portal, it features automated file updates, enhanced security, and strict isolation of local game settings.
            </div>
        </div>

        <div class="faq-item">
            <span class="faq-question">Do I need to install patches manually?</span>
            <div class="faq-answer">
                No. Every shard on Portal NR essentially has its own integrated launcher. When a shard administrator updates files, the system detects the change via SHA-256 hashing. Clicking "Connect" automatically synchronizes all necessary files.
            </div>
        </div>

        <div class="faq-item">
            <span class="faq-question">Does every shard require a full game installation?</span>
            <div class="faq-answer">
                No. This is one of <span class="highlight">Portal NR's</span> most powerful features. The launcher uses a virtualization logic: it relies on your base DAoC installation and only provides shard-specific files "virtually." You only download the files that actually differ from the standard client, saving gigabytes of disk space.
            </div>
        </div>

        <div class="faq-item">
            <span class="faq-question">Where are the received files from Freeshards stored?</span>
            <div class="faq-answer">
                To prevent permission conflicts and "mixed" settings, Portal NR uses dedicated directories under:<br>
                <code style="color:#c5a059">Documents\Electronic Arts\Dark Age of Camelot\PortalNR\[ShardName]\</code><br>
                This is where your <span class="highlight">user.dat</span>, screenshots, and logs are kept, safely separated for each server.
            </div>
        </div>

        <div class="faq-item">
            <span class="faq-question">Why does it say "Launcher Missing"?</span>
            <div class="faq-answer">
                The web portal requires a small local background service (the Launcher) to handle file patching and secure game execution. Without this tool, the website cannot communicate with your PC.
            </div>
        </div>

        <div class="faq-item">
            <span class="faq-question">How secure is my account data?</span>
            <div class="faq-answer">
                We use <span class="highlight">Peppered Hashing</span>. Your passwords are not just encrypted; they are protected by an additional secret server-side key. This makes traditional attacks like rainbow tables virtually impossible.
            </div>
        </div>

    </div>
</div>

<div class="nrfooter">
    &copy; <?php echo date("Y"); ?> ORTAL NOSTALGIC REVIVAL 1.0<br>
</div>

</body>
</html>