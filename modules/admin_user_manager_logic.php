<?php
/**
 * ADMIN USER MANAGER LOGIC - Aldhran V0.6 "Nexus"
 * Real-time Sync between Dacmo CMS & DOL Server
 */
if (!isset($db)) require_once('includes/db.php');

// Sicherheitscheck: Nur Admins (Priv 10) dürfen hier agieren
$checkPriv = (int)($_SESSION['priv_level'] ?? 0);
if ($checkPriv < 10) {
    die("Unauthorized access to nexus core.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $action = $_POST['action'];

    // 1. Zuerst den User im CMS identifizieren
    $user_res = $db->query("SELECT username, email FROM users WHERE id = $target_user_id");
    if ($user_res->num_rows === 0) {
        header("Location: index.php?p=permissions&err=user_not_found");
        exit;
    }
    $user_data = $user_res->fetch_assoc();
    $username = mysqli_real_escape_string($db, $user_data['username']);

    // 2. Logik basierend auf der Aktion
    switch ($action) {
        
        case 'update_standing':
            $new_standing = mysqli_real_escape_string($db, $_POST['standing']);
            
            // A. Update im CMS
            $db->query("UPDATE users SET standing = '$new_standing' WHERE id = $target_user_id");

            // B. Synchronisation mit DOL Server (Tabelle: account)
            if ($new_standing === 'Suspended') {
                $reason = "Suspended by Administrator via CMS Management Hub.";
                // In DOL: Banned = 1 bedeutet der Account kommt nicht mehr rein
                $db->query("UPDATE account SET Banned = 1, BanReason = '$reason' WHERE Name = '$username'");
            } 
            elseif ($new_standing === 'Active') {
                // Account wieder freischalten
                $db->query("UPDATE account SET Banned = 0, BanReason = '' WHERE Name = '$username'");
            }
            
            logAction($db, $_SESSION['user_id'], $target_user_id, 'STANDING_CHANGE', "Changed standing to $new_standing (Sync with DOL)");
            header("Location: index.php?p=permissions&msg=sync_success");
            break;

        case 'change_privilege':
            $new_priv = (int)$_POST['priv_level'];
            
            // CMS Update
            $db->query("UPDATE users SET priv_level = $new_priv WHERE id = $target_user_id");
            
            // DOL Update (Privilevel im Spiel für GM-Rechte)
            // Hinweis: DOL nutzt oft 'PrivLevel' (1=Player, 2=Counselor, 3=GM, etc.)
            $db->query("UPDATE account SET PrivLevel = $new_priv WHERE Name = '$username'");
            
            logAction($db, $_SESSION['user_id'], $target_user_id, 'PRIV_CHANGE', "Set PrivLevel to $new_priv");
            header("Location: index.php?p=permissions&msg=priv_success");
            break;

        case 'delete_account':
            // Vorsicht: Löscht im CMS und setzt Account in DOL auf Banned (statt Hard Delete)
            $db->query("DELETE FROM users WHERE id = $target_user_id");
            $db->query("UPDATE account SET Banned = 1, BanReason = 'Account deleted via CMS' WHERE Name = '$username'");
            
            header("Location: index.php?p=permissions&msg=delete_success");
            break;
    }
    exit;
}