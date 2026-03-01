<?php
/**
 * Aldhran Freeshard - Header
 * Version: 2.0.0 - SECURITY: PDO Migration & HTTPS Enforcement
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once('includes/db.php');

// --- SERVER STATUS LOGIK ---
$server_ip   = "5.249.163.117"; 
$server_port = 10300;
$server_online = false;

$fp = @fsockopen($server_ip, $server_port, $errno, $errstr, 1);
if ($fp) {
    $server_online = true;
    fclose($fp);
}

// User & Access Check via PDO
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt_u = $db->prepare("SELECT standing, priv_level FROM users WHERE id = ?");
    $stmt_u->execute([$uid]);
    $u_data = $stmt_u->fetch();

    if ($u_data) {
        $_SESSION['standing'] = (int)$u_data['standing'];
        if ($_SESSION['standing'] >= 5) {
            $_SESSION['priv_level'] = 0;
        } else {
            $_SESSION['priv_level'] = (int)$u_data['priv_level'];
        }
    }
}

$page = $_GET['p'] ?? 'home'; 

// Fallback: Falls index.php die Daten noch nicht bereitgestellt hat
if (!isset($data) && $page) {
    $stmt_p = $db->prepare("SELECT title, content FROM pages WHERE slug = ?");
    $stmt_p->execute([$page]);
    $data = $stmt_p->fetch();
}

// --- DYNAMISCHE META-TAGS (HTTPS & SEO) ---
$meta_title = h($data['title'] ?? "Aldhran Freeshard - Chronicles of Atlantis");
$raw_content = $data['content'] ?? "Explore the realms of Atlantis. Join the Aldhran Freeshard today.";
$meta_desc = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($raw_content))), 0, 160) . "...";

// Nutzt die SITE_URL Konstante aus der db.php für korrekte HTTPS-Links
$current_url = SITE_URL . "/index.php?p=" . h($page);
$logo_url = SITE_URL . "/assets/img/logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Aldhran - <?php echo $meta_title; ?></title>
    <meta name="title" content="Aldhran - <?php echo $meta_title; ?>">
    <meta name="description" content="<?php echo $meta_desc; ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $current_url; ?>">
    <meta property="og:title" content="Aldhran - <?php echo $meta_title; ?>">
    <meta property="og:description" content="<?php echo $meta_desc; ?>">
    <meta property="og:image" content="<?php echo $logo_url; ?>">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $current_url; ?>">
    <meta property="twitter:title" content="Aldhran - <?php echo $meta_title; ?>">
    <meta property="twitter:description" content="<?php echo $meta_desc; ?>">
    <meta property="twitter:image" content="<?php echo $logo_url; ?>">

    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <div class="header-content">
        <div class="header-left">
             <div class="header-logo-container">
                 <a href="index.php?p=home">
                    <img src="assets/img/logo.png" alt="Aldhran Logo" class="header-logo-img">
                 </a>
                 <div class="header-divider"></div>
                 <span class="server-label">SERVER: 
                    <?php if ($server_online): ?>
                        <span class="status-online" style="color: #00ff00; font-weight: bold;">ONLINE</span>
                    <?php else: ?>
                        <span class="status-offline" style="color: #ff4444; font-weight: bold;">OFFLINE</span>
                    <?php endif; ?>
                 </span>
                 
                 <div class="header-divider"></div>
                 <a href="https://discord.gg/tKf9GAdG" target="_blank" class="discord-link" style="color: #7289da; text-decoration: none; font-size: 1.2em; display: flex; align-items: center; gap: 5px;">
                    <i class="fab fa-discord"></i>
                    <span style="font-size: 0.7em; letter-spacing: 1px; color: #fff; font-family: 'Cinzel', serif;">DISCORD</span>
                 </a>
             </div>
        </div>
        <div class="user-status">
            <?php if (isset($_SESSION['user_id'])): ?>
                <strong class="user-status-name"><?php echo h($_SESSION['username'] ?? 'User'); ?></strong>
                <a href="?p=logout" class="header-auth-link">Logout</a>
            <?php else: ?>
                <a href="?p=login" class="header-auth-link">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>