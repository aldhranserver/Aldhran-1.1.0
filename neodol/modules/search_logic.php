<?php
/**
 * GLOBAL SEARCH LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Shadow-Ban Protection
 */
if (!defined('IN_CMS')) exit;

// Helper laden, damit parseBBCode() bekannt ist
require_once('includes/spike_bb_helper.php'); 

// Wir nutzen das globale PDO Objekt $db
global $db;

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [
    'users' => [],
    'threads' => [],
    'posts' => []
];

// Privilegien für Shadow-Ban Filter
$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$myId   = (int)($_SESSION['user_id'] ?? 0);

if (mb_strlen($query) >= 3) {
    // Suchstring für LIKE vorbereiten (PDO kümmert sich um das Escaping)
    $s = "%" . $query . "%";

    // 1. User suchen (Shadow-Ban Schutz)
    $sql_u = "SELECT id, username, avatar_url, is_shadow_banned FROM users WHERE username LIKE ? ";
    if ($myPriv < 4) { 
        $sql_u .= " AND (is_shadow_banned = 0 OR id = ?) "; 
        $params_u = [$s, $myId];
    } else {
        $params_u = [$s];
    }
    
    $stmt_u = $db->prepare($sql_u . " LIMIT 5");
    $stmt_u->execute($params_u);
    $results['users'] = $stmt_u->fetchAll();

    // 2. Forenthemen suchen (Inkl. Author Shadow-Ban Check)
    $sql_t = "SELECT t.id, t.title, u.is_shadow_banned 
              FROM spike_threads t 
              LEFT JOIN users u ON t.author_id = u.id 
              WHERE t.title LIKE ? ";
    if ($myPriv < 4) { 
        $sql_t .= " AND (u.is_shadow_banned = 0 OR t.author_id = ?) "; 
        $params_t = [$s, $myId];
    } else {
        $params_t = [$s];
    }
    
    $stmt_t = $db->prepare($sql_t . " LIMIT 10");
    $stmt_t->execute($params_t);
    $results['threads'] = $stmt_t->fetchAll();

    // 3. Forenbeiträge suchen
    $sql_p = "SELECT p.id, p.thread_id, p.content, t.title, u.is_shadow_banned, p.author_id
              FROM spike_posts p 
              JOIN spike_threads t ON p.thread_id = t.id 
              LEFT JOIN users u ON p.author_id = u.id
              WHERE p.content LIKE ? ";
    if ($myPriv < 4) { 
        $sql_p .= " AND (u.is_shadow_banned = 0 OR p.author_id = ?) "; 
        $params_p = [$s, $myId];
    } else {
        $params_p = [$s];
    }
    
    $stmt_p = $db->prepare($sql_p . " LIMIT 10");
    $stmt_p->execute($params_p);
    $results['posts'] = $stmt_p->fetchAll();
}
?>