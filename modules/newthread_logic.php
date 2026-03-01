<?php
/**
 * NEWTHREAD LOGIC - Spike Forum
 */
if (!defined('IN_CMS')) { exit; }

$board_id = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
$myPriv   = (int)($_SESSION['priv_level'] ?? 0);
$myId     = (int)($_SESSION['user_id'] ?? 0);

// Board-Check: Existiert das Board und darf der User hier posten?
$stmt = $conn->prepare("SELECT b.title, b.min_priv_post, c.min_priv_post as cat_min_post 
                        FROM spike_boards b 
                        JOIN spike_categories c ON b.cat_id = c.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $board_id);
$stmt->execute();
$board = $stmt->get_result()->fetch_assoc();

if (!$board) { header("Location: index.php?p=spike&err=not_found"); exit; }

$required_bs = ($board['min_priv_post'] > 0) ? (int)$board['min_priv_post'] : (int)$board['cat_min_post'];

if ($myId <= 0 || $myPriv < $required_bs) {
    header("Location: index.php?p=viewboard&id=$board_id&err=unauthorized_post");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thread'])) {
    $title = trim($_POST['thread_title'] ?? '');
    $content = trim($_POST['thread_content'] ?? '');

    if (!empty($title) && !empty($content)) {
        $ins_t = $conn->prepare("INSERT INTO spike_threads (board_id, author_id, title) VALUES (?, ?, ?)");
        $ins_t->bind_param("iis", $board_id, $myId, $title);
        
        if ($ins_t->execute()) {
            $new_id = $conn->insert_id;
            $ins_p = $conn->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
            $ins_p->bind_param("iis", $new_id, $myId, $content);
            $ins_p->execute();
            
            $conn->query("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = $myId");
            header("Location: index.php?p=viewthread&id=$new_id&msg=thread_created");
            exit;
        }
    }
}