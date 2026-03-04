<?php
/**
 * SPIKE MODERATION LOGIC
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */
if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO Objekt $db
global $db;

$myPriv = (int)($userPriv ?? 0); 
$myId   = (int)($currentUserId ?? 0);

// Basis-Check: Nur Moderatoren (3+) dürfen hier überhaupt rein
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $myPriv < 3) {
    header("Location: index.php?p=forum");
    exit;
}

// CSRF Schutz: Moderations-Aktionen sind Primärziele für Angriffe
checkToken($_POST['csrf_token'] ?? '');

$action = $_POST['mod_action'] ?? 'NONE';
$t_id   = (int)($_POST['thread_id'] ?? 0);
$b_id   = (int)($_POST['board_id'] ?? 0);
$p_id   = (int)($_POST['post_id'] ?? 0);

switch ($action) {
    case 'toggle_lock':
        $stmt = $db->prepare("UPDATE spike_threads SET is_locked = 1 - is_locked WHERE id = ?");
        $stmt->execute([$t_id]);
        aldhran_log("MOD_LOCK", "Toggled lock for thread #$t_id", $myId);
        break;

    case 'toggle_sticky':
        $stmt = $db->prepare("UPDATE spike_threads SET is_sticky = 1 - is_sticky WHERE id = ?");
        $stmt->execute([$t_id]);
        aldhran_log("MOD_STICKY", "Toggled sticky for thread #$t_id", $myId);
        break;

    case 'delete_post':
        // Sicherheits-Check: Der erste Post eines Threads darf nicht einzeln gelöscht werden
        $stmt_check = $db->prepare("SELECT id FROM spike_posts WHERE thread_id = ? ORDER BY created_at ASC LIMIT 1");
        $stmt_check->execute([$t_id]);
        $first_post = $stmt_check->fetch();
        
        if ($p_id > 0 && $first_post && (int)$first_post['id'] !== $p_id) {
            $stmt_del = $db->prepare("DELETE FROM spike_posts WHERE id = ?");
            $stmt_del->execute([$p_id]);
            aldhran_log("MOD_DELETE_POST", "Deleted post #$p_id in thread #$t_id", $myId);
        }
        break;

    case 'delete_thread':
        // Thread löschen erfordert Admin-Rechte (4+)
        if ($myPriv >= 4) {
            try {
                $db->beginTransaction();
                
                // Zuerst alle Posts des Threads löschen
                $stmt_p = $db->prepare("DELETE FROM spike_posts WHERE thread_id = ?");
                $stmt_p->execute([$t_id]);
                
                // Dann den Thread-Header löschen
                $stmt_t = $db->prepare("DELETE FROM spike_threads WHERE id = ?");
                $stmt_t->execute([$t_id]);
                
                aldhran_log("MOD_DELETE_THREAD", "Permanently deleted thread #$t_id", $myId);
                
                $db->commit();
                
                $target = ($b_id > 0) ? "viewboard&id=$b_id" : "forum";
                header("Location: index.php?p=$target&msg=deleted");
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                die("The ritual of destruction failed. Error logged.");
            }
        }
        break;
}

// Standard-Redirect zurück zum Thread
header("Location: index.php?p=viewthread&id=$t_id&msg=success");
exit;