<?php
/**
 * SPIKE LOGIC - Aldhran Forum
 * Version: 2.0.0 - SECURITY: PDO Migration & Optimized Sub-Queries
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$userPriv = (int)($_SESSION['priv_level'] ?? 0);

// --- 1. FORENSTRUKTUR LADEN (PDO) ---
// Wir holen nur Kategorien, die der User auch sehen darf
$stmt_cat = $db->prepare("SELECT * FROM spike_categories WHERE min_priv <= ? ORDER BY pos ASC");
$stmt_cat->execute([$userPriv]);
$categories = $stmt_cat->fetchAll();

$forum_structure = [];

if ($categories) {
    foreach ($categories as $cat) {
        $cat_id = (int)$cat['id'];
        
        // Wir holen die Boards inkl. Statistiken via PDO
        // Hinweis: Die Sub-Queries wurden für PDO optimiert
        $stmt_board = $db->prepare("
            SELECT b.*, 
            (SELECT COUNT(*) FROM spike_threads WHERE board_id = b.id) as thread_count,
            (SELECT COUNT(*) FROM spike_posts p2 JOIN spike_threads t2 ON p2.thread_id = t2.id WHERE t2.board_id = b.id) as post_count,
            (SELECT p3.created_at FROM spike_posts p3 JOIN spike_threads t3 ON p3.thread_id = t3.id WHERE t3.board_id = b.id ORDER BY p3.id DESC LIMIT 1) as last_post_date,
            (SELECT u3.username FROM spike_posts p4 JOIN spike_threads t4 ON p4.thread_id = t4.id JOIN users u3 ON p4.author_id = u3.id WHERE t4.board_id = b.id ORDER BY p4.id DESC LIMIT 1) as last_post_user
            FROM spike_boards b 
            WHERE b.cat_id = ? 
            ORDER BY b.pos ASC
        ");
        $stmt_board->execute([$cat_id]);
        $boards = $stmt_board->fetchAll();

        $cat_boards = [];
        if ($boards) {
            foreach ($boards as $b) {
                $b['thread_count'] = (int)$b['thread_count'];
                $b['post_count']   = (int)$b['post_count'];
                $b['cat_min_post'] = (int)($cat['min_priv_post'] ?? 0);
                $cat_boards[] = $b;
            }
        }
        $forum_structure[] = ['info' => $cat, 'boards' => $cat_boards];
    }
}

// --- 2. ONLINE USERS LOGIK (STRIKTE 5 MINUTEN) ---
$online_limit = time() - 300;

$stmt_online = $db->prepare("
    SELECT username, priv_level 
    FROM users 
    WHERE last_activity > ? 
    ORDER BY username ASC
");
$stmt_online->execute([$online_limit]);
$online_users = $stmt_online->fetchAll();