<?php
/**
 * HOME LOGIC - Aldhran Dashboard
 * Version: 2.0.0 - SECURITY: PDO Migration & Spike Forum Sync
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen jetzt das globale PDO Objekt $db aus der db.php
global $db;

// Wir holen die 5 neuesten Themen aus allen Boards, die für den User sichtbar sind
// PDO Syntax mit Prepared Statement für das User-Privileg
$stmt_threads = $db->prepare("
    SELECT t.id, t.title, t.created_at, u.username, u.avatar_url, b.title as board_title
    FROM spike_threads t
    JOIN users u ON t.author_id = u.id
    JOIN spike_boards b ON t.board_id = b.id
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE c.min_priv <= :priv
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Sicherheit: Wir binden das Privileg-Level des Users ein
$stmt_threads->bindValue(':priv', (int)$userPriv, PDO::PARAM_INT);
$stmt_threads->execute();

// Alle Ergebnisse in ein Array laden
$latest_threads = $stmt_threads->fetchAll();
?>