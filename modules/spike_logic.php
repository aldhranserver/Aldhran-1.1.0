<?php
/**
 * SPIKE LOGIC - Aldhran Forum
 * Version: 1.2.5 - FIXED: Strict Real-Time Online via Timestamp
 */
if (!defined('IN_CMS')) { exit; }

$userPriv = (int)($_SESSION['priv_level'] ?? 0);

// --- 1. FORENSTRUKTUR LADEN ---
$cat_res = $conn->query("SELECT * FROM spike_categories WHERE min_priv <= $userPriv ORDER BY pos ASC");
$forum_structure = [];

if ($cat_res) {
    while ($cat = $cat_res->fetch_assoc()) {
        $cat_id = (int)$cat['id'];
        
        $board_res = $conn->query("
            SELECT b.*, 
            (SELECT COUNT(*) FROM spike_threads WHERE board_id = b.id) as thread_count,
            (SELECT COUNT(*) FROM spike_posts p2 JOIN spike_threads t2 ON p2.thread_id = t2.id WHERE t2.board_id = b.id) as post_count,
            (SELECT p3.created_at FROM spike_posts p3 JOIN spike_threads t3 ON p3.thread_id = t3.id WHERE t3.board_id = b.id ORDER BY p3.id DESC LIMIT 1) as last_post_date,
            (SELECT u3.username FROM spike_posts p4 JOIN spike_threads t4 ON p4.thread_id = t4.id JOIN users u3 ON p4.author_id = u3.id WHERE t4.board_id = b.id ORDER BY p4.id DESC LIMIT 1) as last_post_user
            FROM spike_boards b 
            WHERE b.cat_id = $cat_id 
            ORDER BY b.pos ASC
        ");

        $cat_boards = [];
        if ($board_res) {
            while ($b = $board_res->fetch_assoc()) {
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
// Berechnung in Sekunden: Aktuelle Zeit minus 300 Sekunden (5 Min)
$online_limit = time() - 300;

$online_users = [];
$online_result = $conn->query("
    SELECT username, priv_level 
    FROM users 
    WHERE last_activity > $online_limit 
    ORDER BY username ASC
");

if ($online_result) {
    while ($u = $online_result->fetch_assoc()) {
        $online_users[] = $u;
    }
}