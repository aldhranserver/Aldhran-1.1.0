<?php
/**
 * NEWTHREAD LOGIC - Spike Forum
 * Version: 2.0.0 - SECURITY: PDO Migration & Atomic Transactions
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$board_id = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
$myPriv   = (int)($_SESSION['priv_level'] ?? 0);
$myId     = (int)($_SESSION['user_id'] ?? 0);

// 1. Board-Check via PDO Prepared Statement
$stmt = $db->prepare("
    SELECT b.title, b.min_priv_post, c.min_priv_post as cat_min_post 
    FROM spike_boards b 
    JOIN spike_categories c ON b.cat_id = c.id 
    WHERE b.id = ?
");
$stmt->execute([$board_id]);
$board = $stmt->fetch();

if (!$board) { 
    header("Location: index.php?p=spike&err=not_found"); 
    exit; 
}

// Berechtigung prüfen
$required_bs = ($board['min_priv_post'] > 0) ? (int)$board['min_priv_post'] : (int)$board['cat_min_post'];

if ($myId <= 0 || $myPriv < $required_bs) {
    header("Location: index.php?p=viewboard&id=$board_id&err=unauthorized_post");
    exit;
}

// 2. Thread Erstellung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thread'])) {
    // Enterprise V2: CSRF Schutz
    checkToken($_POST['csrf_token'] ?? '');

    $title = trim($_POST['thread_title'] ?? '');
    $content = trim($_POST['thread_content'] ?? '');

    if (!empty($title) && !empty($content)) {
        try {
            // ATOMARE TRANSAKTION STARTEN
            $db->beginTransaction();

            // A. Thread Header einfügen
            $ins_t = $db->prepare("INSERT INTO spike_threads (board_id, author_id, title) VALUES (?, ?, ?)");
            $ins_t->execute([$board_id, $myId, $title]);
            $new_thread_id = $db->lastInsertId();

            // B. Ersten Post (den Inhalt) einfügen
            $ins_p = $db->prepare("INSERT INTO spike_posts (thread_id, author_id, content) VALUES (?, ?, ?)");
            $ins_p->execute([$new_thread_id, $myId, $content]);
            
            // C. User Statistik erhöhen
            $upd_u = $db->prepare("UPDATE users SET forum_posts = forum_posts + 1 WHERE id = ?");
            $upd_u->execute([$myId]);

            // Logging im Waschsalon
            aldhran_log("THREAD_CREATED", "New thread '$title' in board #$board_id", $myId, $new_thread_id);

            // ALLES OK? DANN SPEICHERN
            $db->commit();

            header("Location: index.php?p=viewthread&id=$new_thread_id&msg=thread_created");
            exit;

        } catch (Exception $e) {
            // BEI FEHLER: ALLES RÜCKGÄNGIG MACHEN
            $db->rollBack();
            error_log("Spike NewThread Error: " . $e->getMessage());
            die("The ritual of creation failed. Please contact a Chronicler.");
        }
    }
}