<?php 
ob_start();
define('IN_CMS', true);    
/**
 * Aldhran Enterprise - Main Index
 * Version: 2.0.0 - SECURITY: PDO Core Migration & Audit Logging
 */
require_once('includes/db.php'); // Nutzt jetzt $db als PDO-Instanz

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- 1. SESSION TIMEOUT & ONLINE STATUS LOGIK ---
$timeout_duration = 1800; 

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    // Update mit PDO
    $stmt_act = $db->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $stmt_act->execute([time(), $uid]);

    // Timeout Check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
        aldhran_log("SESSION_TIMEOUT", "User session expired", $uid);
        session_unset();
        session_destroy();
        header("Location: index.php?p=login&timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Auth-Check (Standing & Bans)
    $stmt_auth = $db->prepare("SELECT id FROM users WHERE id = ? AND standing < 5");
    $stmt_auth->execute([$uid]);
    
    if (!$stmt_auth->fetch()) {
        aldhran_log("AUTH_KICK", "User kicked due to standing/ban", $uid);
        session_destroy();
        header("Location: index.php?p=login&msg=session_invalid");
        exit;
    }
}

// --- GLOBALE VARIABLEN ---
$page_slug = $_GET['p'] ?? 'home'; 
$userPriv = (int)($_SESSION['priv_level'] ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$can_edit = ($userPriv >= 4);

// --- SPIKE NOTIFICATION LOGIK ---
$unread_count = 0;
if ($currentUserId > 0) {
    $stmt_notif = $db->prepare("SELECT COUNT(id) as cnt FROM spike_notifications WHERE user_id = ? AND is_read = 0");
    $stmt_notif->execute([$currentUserId]);
    $unread_count = (int)$stmt_notif->fetch()['cnt'];
}

// Logout handling
if ($page_slug === 'logout') {
    require_once('logout.php');
    exit; 
}

$myStanding = (int)($_SESSION['user_standing'] ?? 0);
$lock_path = __DIR__ . '/maintenance.lock';

if ($myStanding >= 5) {
    session_destroy();
    die("Dein Zugang zu Aldhran wurde permanent gesperrt.");
}

// Maintenance Toggle (Enterprise Logging)
if ($userPriv >= 5 && isset($_POST['toggle_maintenance'])) {
    if (file_exists($lock_path)) { 
        @unlink($lock_path); 
        aldhran_log("MAINTENANCE_OFF", "Maintenance mode disabled by Admin", $currentUserId);
    } else { 
        @file_put_contents($lock_path, 'ACTIVE'); 
        aldhran_log("MAINTENANCE_ON", "Maintenance mode enabled by Admin", $currentUserId);
    }
    header("Location: index.php?p=" . $page_slug);
    exit;
}

clearstatcache();
$is_maintenance = file_exists($lock_path);

if ($is_maintenance && $userPriv < 5 && $page_slug !== 'login') {
    $stmt_m = $db->prepare("SELECT value FROM settings WHERE setting_key = 'maintenance_text' LIMIT 1");
    $stmt_m->execute();
    $m_data = $stmt_m->fetch();
    $msg = $m_data['value'] ?? "Under Maintenance.";
    while (ob_get_level()) { ob_end_clean(); }
    die("<html style='background:#050505;'><body style='margin:0;background:#050505;color:#d4af37;display:flex;justify-content:center;align-items:center;height:100vh;font-family:serif;'><div style='border:1px solid #d4af37;padding:50px;text-align:center;box-shadow:0 0 30px rgba(212,175,55,0.3);max-width:600px;background:#000;'><img src='assets/img/logo.png' style='max-width:250px;margin-bottom:20px;'><h1 style='letter-spacing:5px;font-family:\"Cinzel\", serif;'>MAINTENANCE</h1><p style='color:#fff;font-style:italic;font-family:sans-serif;line-height:1.6;'>$msg</p><div style='margin-top:30px;'><a href='index.php?p=login' style='color:#d4af37;text-decoration:none;border:1px solid #d4af37;padding:10px 20px;font-size:0.8em;letter-spacing:2px;'><i class='fas fa-sign-in-alt'>Staff Login</i></a></div></div></body></html>");
}

// Page Data laden (Sicher via PDO)
$stmt_page = $db->prepare("SELECT title, content FROM pages WHERE slug = ?");
$stmt_page->execute([$page_slug]);
$data = $stmt_page->fetch();

// --- ARCHITECT MODE SAVE LOGIK ---
if ($can_edit && isset($_POST['update_page_content'])) {
    // CSRF Check hinzufügen!
    checkToken($_POST['csrf_token'] ?? '');
    
    $target_slug = $_POST['target_slug'];
    $page_title = $_POST['page_title'];
    $page_content = $_POST['page_content'];
    
    // PDO Upsert Logik
    $stmt_save = $db->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)");
    
    if ($stmt_save->execute([$target_slug, $page_title, $page_content])) {
        aldhran_log("PAGE_EDIT", "Page '$target_slug' updated via Architect Mode", $currentUserId);
        header("Location: index.php?p=" . urlencode($target_slug) . "&msg=success"); 
    } else {
        die("Error saving chronic.");
    }
    exit;
}

// --- LOGIC DISPATCHER ---
$logic_to_include = "";
if ($page_slug === 'discord_callback') { $logic_to_include = 'includes/discord_logic.php'; } 
elseif ($page_slug === 'um' && $can_edit) { $logic_to_include = 'modules/um_logic.php'; } 
elseif ($page_slug === 'admin_log' && $can_edit) { $logic_to_include = 'modules/admin_log_logic.php'; } 
else {
    $normal_logic = "modules/" . $page_slug . "_logic.php";
    $spike_logic = "modules/spike_" . $page_slug . "_logic.php";
    if (file_exists($normal_logic)) { $logic_to_include = $normal_logic; } 
    elseif (file_exists($spike_logic)) { $logic_to_include = $spike_logic; }
}
if (!empty($logic_to_include)) { include($logic_to_include); }

$meta_title = h($data['title'] ?? "Aldhran Freeshard - Chronicles of Atlantis");
$meta_desc = mb_substr(strip_tags($data['content'] ?? "Explore the realms of Atlantis."), 0, 160) . "...";

require_once('header.php'); 
?>
<div class="main-container">
    <?php if (!isset($hide_sidebar) || $hide_sidebar === false) { require_once('sidebar.php'); } ?>
    
    <main class="content-area <?php echo (isset($hide_sidebar) && $hide_sidebar) ? 'full-width' : ''; ?>">
        <article>
            <div class="article-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #222; padding-bottom: 10px; margin-bottom: 20px;">
                <h1 class="article-title" style="margin:0;">
                    <?php 
                    if($page_slug === 'um') { echo "User Management"; } 
                    elseif($page_slug === 'admin_log') { echo "Admin Logs"; } 
                    else {
                        $fallback_title = str_replace('_', ' ', $page_slug);
                        echo h($data['title'] ?? ucwords($fallback_title)); 
                    }
                    ?>
                </h1>
                
                <div class="header-actions" style="display: flex; gap: 15px; align-items: center;">
                    <?php if ($currentUserId > 0): ?>
                        <a href="?p=notifications" style="text-decoration:none; position:relative; color: <?php echo ($unread_count > 0) ? '#d4af37' : '#555'; ?>;">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span style="position:absolute; top:-8px; right:-10px; background:#ff4444; color:#fff; font-size:9px; padding:2px 5px; border-radius:50%; font-family:sans-serif; border:1px solid #000;">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($userPriv >= 5): ?>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="toggle_maintenance" 
                                style="font-size: 10px; border: 1px solid <?php echo $is_maintenance ? '#ff4444' : '#d4af37'; ?>; color: <?php echo $is_maintenance ? '#ff4444' : '#d4af37'; ?>; background: rgba(0,0,0,0.2); cursor: pointer; padding: 5px 10px;">
                            <i class="fas fa-tools"></i> <?php echo $is_maintenance ? 'MAINTENANCE' : 'LIVE'; ?>
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if($can_edit): ?>
                    <a href="index.php?p=<?php echo h($page_slug); ?>&edit_mode=1" style="border: 1px solid #d4af37; color: #d4af37; padding: 5px 10px; text-decoration: none; font-size: 11px; text-transform: uppercase;">
                        <i class="fas fa-edit"></i> Edit (AM)
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-body">
                <?php 
                if (isset($_GET['edit_mode']) && $can_edit) {
                    ?>
                    <div class="architect-editor-wrap" style="margin-bottom: 40px; padding: 25px; border: 1px dashed #d4af37; background: rgba(212, 175, 55, 0.05);">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                            <input type="hidden" name="target_slug" value="<?php echo h($page_slug); ?>">
                            
                            <label style="display:block; font-size: 10px; color: #888; text-transform: uppercase;">Page Title</label>
                            <input type="text" name="page_title" value="<?php echo h($data['title'] ?? ''); ?>" style="width:100%; margin-bottom:20px; background:#000; border: 1px solid #333; color: #fff; padding: 10px;">
                            <label style="display:block; font-size: 10px; color: #888; text-transform: uppercase;">Content</label>
                            <textarea id="editor" name="page_content"><?php echo $data['content'] ?? ''; ?></textarea>
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" name="update_page_content" style="background:#d4af37; color:#000; border:none; padding:10px 20px; cursor:pointer; font-weight:bold;">CHRONIK SPEICHERN</button>
                                <a href="?p=<?php echo h($page_slug); ?>" style="color: #888; text-decoration:none; padding:10px;">CANCEL</a>
                            </div>
                        </form>
                        <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
                        <script>ClassicEditor.create(document.querySelector('#editor')).catch(e=>console.error(e));</script>
                    </div>
                    <?php
                }

                if (!isset($_GET['edit_mode']) && !empty($data['content'])) {
                    echo '<div class="dynamic-content" style="margin-bottom: 30px;">'.$data['content'].'</div>';
                }

                $view_to_include = "";
                $module_view = "modules/" . $page_slug . "_view.php";
                $spike_view = "modules/spike_" . $page_slug . "_view.php";
                $direct_file = __DIR__ . '/' . $page_slug . ".php";

                if (file_exists($module_view)) { $view_to_include = $module_view; } 
                elseif (file_exists($spike_view)) { $view_to_include = $spike_view; } 
                elseif (file_exists($direct_file)) { $view_to_include = $direct_file; }

                if (!empty($view_to_include) && !isset($_GET['edit_mode'])) { 
                    include($view_to_include); 
                } elseif (empty($data['content']) && !isset($_GET['edit_mode'])) {
                    echo '<div class="info-msg">The realm you are looking for does not exist in our chronicles.</div>';
                }
                ?>
            </div>
        </article>
    </main>
</div>
<?php require_once('footer.php'); ob_end_flush(); ?>