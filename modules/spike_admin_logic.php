<?php
/**
 * SPIKE ADMIN LOGIC - Forum Architect & Security
 * Version: 2.9.5 - FINAL: Cross-Category Drag & Drop Fix
 */
if (!defined('IN_CMS')) { exit; }

if ($userPriv < 4) { 
    header("Location: index.php?p=home"); 
    exit; 
}

function logAdminAction($conn, $type, $details) {
    global $myId;
    $admin_id = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : (int)$myId;
    $type = mysqli_real_escape_string($conn, $type);
    $details = mysqli_real_escape_string($conn, $details);
    $sql = "INSERT INTO admin_logs (`admin_id`, `action_type`, `details`, `created_at`) 
            VALUES ($admin_id, '$type', '$details', NOW())";
    $conn->query($sql);
}

// --- AJAX HANDLER ---
if (isset($_POST['ajax_action'])) {
    if (ob_get_length()) ob_clean(); 
    
    // Matrix Update
    if ($_POST['ajax_action'] === 'update_matrix') {
        if (isset($_POST['cat_perms'])) {
            foreach ($_POST['cat_perms'] as $cid => $p) {
                $cid = (int)$cid; $v = (int)$p['v']; $p_post = (int)$p['p'];
                $conn->query("UPDATE `spike_categories` SET `min_priv` = $v, `min_priv_post` = $p_post WHERE `id` = $cid");
            }
        }
        if (isset($_POST['board_perms'])) {
            foreach ($_POST['board_perms'] as $bid => $p) {
                $bid = (int)$bid; $v = (int)$p['v']; $p_post = (int)$p['p'];
                $conn->query("UPDATE `spike_boards` SET `min_priv` = $v, `min_priv_post` = $p_post WHERE `id` = $bid");
            }
        }
        logAdminAction($conn, 'FORUM_MATRIX', 'Updated global permission matrix');
        echo "success"; exit;
    }

    // Drag & Drop Sortierung (Kategorien)
    if ($_POST['ajax_action'] === 'sort_cats' && isset($_POST['order'])) {
        foreach ($_POST['order'] as $pos => $id) {
            $id = (int)$id; $pos = (int)$pos + 1;
            $conn->query("UPDATE `spike_categories` SET `pos` = $pos WHERE `id` = $id");
        }
        echo "success"; exit;
    }

    // Drag & Drop Sortierung (Boards) - MIT KATEGORIE-WECHSEL FIX
    if ($_POST['ajax_action'] === 'sort_boards' && isset($_POST['order'])) {
        $target_cat = (int)$_POST['target_cat_id'];
        foreach ($_POST['order'] as $pos => $id) {
            $id = (int)$id; $pos = (int)$pos + 1;
            // Setzt neue Position UND neue Kategorie-Zugehörigkeit
            $conn->query("UPDATE `spike_boards` SET `pos` = $pos, `cat_id` = $target_cat WHERE `id` = $id");
        }
        logAdminAction($conn, 'FORUM_SORT', "Moved boards to cat $target_cat");
        echo "success"; exit;
    }

    if ($_POST['ajax_action'] === 'recalc') {
        $conn->query("UPDATE users SET forum_posts = 0");
        $conn->query("UPDATE users u SET u.forum_posts = (SELECT COUNT(*) FROM spike_posts p WHERE p.author_id = u.id)");
        logAdminAction($conn, 'FORUM_MAINTENANCE', 'Recalculated post counts');
        echo "success"; exit;
    }
}

// --- REDIRECT ACTIONS ---
if (isset($_POST['add_cat'])) { 
    $t = mysqli_real_escape_string($conn, $_POST['cat_title']); 
    $conn->query("INSERT INTO `spike_categories` (`title`, `pos`, `min_priv`, `min_priv_post`) VALUES ('$t', 99, 0, 1)"); 
    header("Location: index.php?p=spike_admin"); exit; 
}

if (isset($_POST['add_subforum'])) {
    $cat_id = (int)$_POST['target_cat_id'];
    $title  = mysqli_real_escape_string($conn, $_POST['board_title']);
    $desc   = mysqli_real_escape_string($conn, $_POST['board_desc']);
    $conn->query("INSERT INTO `spike_boards` (`cat_id`, `title`, `description`, `pos`, `min_priv`, `min_priv_post`) VALUES ($cat_id, '$title', '$desc', 99, 0, 1)");
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_POST['update_board_desc'])) {
    $bid  = (int)$_POST['edit_board_id'];
    $desc = mysqli_real_escape_string($conn, $_POST['new_board_desc']);
    $conn->query("UPDATE `spike_boards` SET `description` = '$desc' WHERE `id` = $bid");
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_GET['del_cat'])) {
    $id = (int)$_GET['del_cat'];
    $conn->query("DELETE FROM `spike_categories` WHERE `id` = $id");
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_GET['del_board'])) {
    $id = (int)$_GET['del_board'];
    $conn->query("DELETE FROM `spike_boards` WHERE `id` = $id");
    header("Location: index.php?p=spike_admin"); exit;
}

$all_cats = $conn->query("SELECT * FROM spike_categories ORDER BY pos ASC");