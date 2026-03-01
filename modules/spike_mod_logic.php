<?php
/**
 * SPIKE MODERATION LOGIC
 * Version: 0.5 - Added Delete Post & Absolute Redirects
 */
if (!defined('IN_CMS')) { exit; }

$logFile = __DIR__ . '/../debug_mod.txt';
$log = function($msg) use ($logFile) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
};

$myPriv = $userPriv; 
$myId   = $currentUserId;

// Basis-Check: Nur Moderatoren (3+) dürfen hier überhaupt rein
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $myPriv < 3) {
    $log("Abbruch: Kein POST oder Priv zu niedrig ($myPriv)");
    header("Location: index.php?p=forum");
    exit;
}

$action = $_POST['mod_action'] ?? 'NONE';
$t_id   = (int)($_POST['thread_id'] ?? 0);
$b_id   = (int)($_POST['board_id'] ?? 0);
$p_id   = (int)($_POST['post_id'] ?? 0);

$log("Aktion: $action | Thread: $t_id | Board: $b_id | Post: $p_id");

switch ($action) {
    case 'toggle_lock':
        $conn->query("UPDATE spike_threads SET is_locked = 1 - is_locked WHERE id = $t_id");
        break;

    case 'toggle_sticky':
        $conn->query("UPDATE spike_threads SET is_sticky = 1 - is_sticky WHERE id = $t_id");
        break;

    case 'delete_post':
        // Sicherheits-Check: Der erste Post eines Threads darf nicht einzeln gelöscht werden (dafür nutzt man delete_thread)
        $first_check = $conn->query("SELECT id FROM spike_posts WHERE thread_id = $t_id ORDER BY created_at ASC LIMIT 1");
        $first_post = $first_check->fetch_assoc();
        
        if ($p_id > 0 && $first_post['id'] != $p_id) {
            $conn->query("DELETE FROM spike_posts WHERE id = $p_id");
            $log("Erfolg: Post $p_id gelöscht.");
        } else {
            $log("Fehler: Post $p_id ist Startpost oder ID ungültig.");
        }
        break;

    case 'delete_thread':
        // Thread löschen erfordert Admin-Rechte (4+)
        if ($myPriv >= 4) {
            $conn->query("DELETE FROM spike_posts WHERE thread_id = $t_id");
            $conn->query("DELETE FROM spike_threads WHERE id = $t_id");
            
            $target = ($b_id > 0) ? "viewboard&id=$b_id" : "forum";
            $log("Erfolg: Thread $t_id gelöscht. Redirect zu $target");
            
            header("Location: index.php?p=$target&msg=deleted");
            exit;
        }
        break;
}

// Standard-Redirect zurück zum Thread
$log("Erfolg: Aktion $action. Redirect zurück zu Thread $t_id");
header("Location: index.php?p=viewthread&id=$t_id&msg=success");
exit;