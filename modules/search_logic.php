<?php
/**
 * GLOBAL SEARCH LOGIC - Aldhran
 * Version: 1.1 - Added BBCode Helper & Shadow-Ban Filter
 */
if (!defined('IN_CMS')) exit;

// Helper laden, damit parseBBCode() bekannt ist
require_once('includes/spike_bb_helper.php'); 

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [
    'users' => [],
    'threads' => [],
    'posts' => []
];

// Privilegien für Shadow-Ban Filter
$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$myId   = (int)($_SESSION['user_id'] ?? 0);

if (strlen($query) >= 3) {
    $s = "%" . mysqli_real_escape_string($conn, $query) . "%";

    // 1. User suchen (Shadow-Ban Schutz)
    $sql_u = "SELECT id, username, avatar_url, is_shadow_banned FROM users WHERE username LIKE '$s' ";
    if ($myPriv < 4) { $sql_u .= " AND (is_shadow_banned = 0 OR id = $myId) "; }
    $res_u = $conn->query($sql_u . " LIMIT 5");
    while($u = $res_u->fetch_assoc()) { $results['users'][] = $u; }

    // 2. Forenthemen suchen (Inkl. Author Shadow-Ban Check)
    $sql_t = "SELECT t.id, t.title, u.is_shadow_banned 
              FROM spike_threads t 
              LEFT JOIN users u ON t.author_id = u.id 
              WHERE t.title LIKE '$s' ";
    if ($myPriv < 4) { $sql_t .= " AND (u.is_shadow_banned = 0 OR t.author_id = $myId) "; }
    $res_t = $conn->query($sql_t . " LIMIT 10");
    while($t = $res_t->fetch_assoc()) { $results['threads'][] = $t; }

    // 3. Forenbeiträge suchen
    $sql_p = "SELECT p.id, p.thread_id, p.content, t.title, u.is_shadow_banned, p.author_id
              FROM spike_posts p 
              JOIN spike_threads t ON p.thread_id = t.id 
              LEFT JOIN users u ON p.author_id = u.id
              WHERE p.content LIKE '$s' ";
    if ($myPriv < 4) { $sql_p .= " AND (u.is_shadow_banned = 0 OR p.author_id = $myId) "; }
    $res_p = $conn->query($sql_p . " LIMIT 10");
    while($p = $res_p->fetch_assoc()) { $results['posts'][] = $p; }
}
?>