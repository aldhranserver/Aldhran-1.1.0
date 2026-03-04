<?php
/**
 * NeoDOL 1.1.4 - Safe Dispatcher
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
define('IN_CMS', true);    

require_once('includes/db.php');

if (session_status() === PHP_SESSION_NONE) { 
    session_name('NEODOL_SESSION');
    session_start(); 
}

// --- HELPER ---
function safe_h($str) {
    return function_exists('h') ? h($str) : htmlspecialchars($str);
}

$page_slug = $_GET['p'] ?? 'home'; 
$userPriv = (int)($_SESSION['priv_level'] ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// --- 1. FETCH PAGE DATA ---
$stmt_page = $db->prepare("SELECT title, content FROM pages WHERE slug = ?");
$stmt_page->execute([$page_slug]);
$data = $stmt_page->fetch();

if (file_exists('header.php')) { include('header.php'); }
?>

<div class="main-container">
    <?php if (file_exists('sidebar.php')) { include('sidebar.php'); } ?>

    <main class="content-area">
        <article>
            <h1 class="article-title"><?php echo safe_h($data['title'] ?? ucwords($page_slug)); ?></h1>
            
            <div class="content-body">
                <?php 
                // DB Content (Nur anzeigen wenn nicht leer)
                if (!empty($data['content'])) {
                    echo '<div class="dynamic-content">' . $data['content'] . '</div>';
                }

                // --- 2. LOGIC DISPATCHER (Verarbeitung) ---
                $logic_file = "modules/" . $page_slug . "_logic.php";
                if (file_exists($logic_file)) {
                    include($logic_file);
                }

                // --- 3. VIEW DISPATCHER (Anzeige) ---
                $view_file = "modules/" . $page_slug . ".php";
                if (file_exists($view_file)) {
                    include($view_file);
                }
                ?>
            </div>
        </article>
    </main>
</div>

<?php 
if (file_exists('footer.php')) { include('footer.php'); }
ob_end_flush(); 
?>