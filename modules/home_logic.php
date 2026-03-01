<?php
/**
 * HOME LOGIC - Aldhran Dashboard
 * Fokus: Top 5 Latest Forum Threads
 */
if (!defined('IN_CMS')) { exit; }

// Wir holen die 5 neuesten Themen aus allen Boards, die für den User (BS) sichtbar sind
$latest_threads_res = $conn->query("
    SELECT t.id, t.title, t.created_at, u.username, u.avatar_url, b.title as board_title
    FROM spike_threads t
    JOIN users u ON t.author_id = u.id
    JOIN spike_boards b ON t.board_id = b.id
    JOIN spike_categories c ON b.cat_id = c.id
    WHERE c.min_priv <= $userPriv
    ORDER BY t.created_at DESC
    LIMIT 5
");

$latest_threads = [];
if ($latest_threads_res) {
    while ($lt = $latest_threads_res->fetch_assoc()) {
        $latest_threads[] = $lt;
    }
}
?>