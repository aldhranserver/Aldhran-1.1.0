<?php
/**
 * SPIKE ADMIN LOGIC - Forum Architect & Security
 * Version: 3.0.0 - SECURITY: PDO Core & Unified Logging
 */
if (!defined('IN_CMS')) { exit; }

// Zugriffsschutz (Staff-Check)
if ($userPriv < 4) { 
    header("Location: index.php?p=home"); 
    exit; 
}

/**
 * Nutzt das neue globale Log-System
 */
function logAdminAction($type, $details) {
    global $myId;
    $admin_id = $_SESSION['userId'] ?? $myId;
    // Greift auf die zentrale Funktion in db.php zurück
    aldhran_log($type, $details, $admin_id);
}

// --- AJAX HANDLER (PDO) ---
if (isset($_POST['ajax_action'])) {
    if (ob_get_length()) ob_clean(); 
    
    // Matrix Update
    if ($_POST['ajax_action'] === 'update_matrix') {
        if (isset($_POST['cat_perms'])) {
            $stmt_cat = $db->prepare("UPDATE spike_categories SET min_priv = ?, min_priv_post = ? WHERE id = ?");
            foreach ($_POST['cat_perms'] as $cid => $p) {
                $stmt_cat->execute([(int)$p['v'], (int)$p['p'], (int)$cid]);
            }
        }
        if (isset($_POST['board_perms'])) {
            $stmt_board = $db->prepare("UPDATE spike_boards SET min_priv = ?, min_priv_post = ? WHERE id = ?");
            foreach ($_POST['board_perms'] as $bid => $p) {
                $stmt_board->execute([(int)$p['v'], (int)$p['p'], (int)$bid]);
            }
        }
        logAdminAction('FORUM_MATRIX', 'Updated global permission matrix');
        echo "success"; exit;
    }

    // Drag & Drop Sortierung (Kategorien)
    if ($_POST['ajax_action'] === 'sort_cats' && isset($_POST['order'])) {
        $stmt_sort = $db->prepare("UPDATE spike_categories SET pos = ? WHERE id = ?");
        foreach ($_POST['order'] as $pos => $id) {
            $stmt_sort->execute([(int)$pos + 1, (int)$id]);
        }
        echo "success"; exit;
    }

    // Drag & Drop Sortierung (Boards) - MIT KATEGORIE-WECHSEL FIX
    if ($_POST['ajax_action'] === 'sort_boards' && isset($_POST['order'])) {
        $target_cat = (int)$_POST['target_cat_id'];
        $stmt_sort_b = $db->prepare("UPDATE spike_boards SET pos = ?, cat_id = ? WHERE id = ?");
        foreach ($_POST['order'] as $pos => $id) {
            $stmt_sort_b->execute([(int)$pos + 1, $target_cat, (int)$id]);
        }
        logAdminAction('FORUM_SORT', "Moved boards to cat $target_cat");
        echo "success"; exit;
    }

    if ($_POST['ajax_action'] === 'recalc') {
        $db->query("UPDATE users SET forum_posts = 0");
        $db->query("UPDATE users u SET u.forum_posts = (SELECT COUNT(*) FROM spike_posts p WHERE p.author_id = u.id)");
        logAdminAction('FORUM_MAINTENANCE', 'Recalculated post counts');
        echo "success"; exit;
    }
}

// --- REDIRECT ACTIONS (PDO Prepared) ---
if (isset($_POST['add_cat'])) { 
    $t = trim($_POST['cat_title'] ?? ''); 
    $stmt = $db->prepare("INSERT INTO spike_categories (title, pos, min_priv, min_priv_post) VALUES (?, 99, 0, 1)");
    $stmt->execute([$t]);
    header("Location: index.php?p=spike_admin"); exit; 
}

if (isset($_POST['add_subforum'])) {
    $cat_id = (int)$_POST['target_cat_id'];
    $title  = trim($_POST['board_title'] ?? '');
    $desc   = trim($_POST['board_desc'] ?? '');
    $stmt = $db->prepare("INSERT INTO spike_boards (cat_id, title, description, pos, min_priv, min_priv_post) VALUES (?, ?, ?, 99, 0, 1)");
    $stmt->execute([$cat_id, $title, $desc]);
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_POST['update_board_desc'])) {
    $bid  = (int)$_POST['edit_board_id'];
    $desc = trim($_POST['new_board_desc'] ?? '');
    $stmt = $db->prepare("UPDATE spike_boards SET description = ? WHERE id = ?");
    $stmt->execute([$desc, $bid]);
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_GET['del_cat'])) {
    $id = (int)$_GET['del_cat'];
    $stmt = $db->prepare("DELETE FROM spike_categories WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?p=spike_admin"); exit;
}

if (isset($_GET['del_board'])) {
    $id = (int)$_GET['del_board'];
    $stmt = $db->prepare("DELETE FROM spike_boards WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?p=spike_admin"); exit;
}

// Daten laden für die View
$all_cats = $db->query("SELECT * FROM spike_categories ORDER BY pos ASC")->fetchAll();