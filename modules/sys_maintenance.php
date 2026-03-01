<?php
require_once('../includes/db.php');
session_start();

if (($_SESSION['priv_level'] ?? 0) < 5) die("Access Denied");

if (isset($_POST['toggle_maint'])) {
    $lock_file = '../maintenance.lock';

    if (file_exists($lock_file)) {
        // WARTUNG AUSSCHALTEN
        unlink($lock_file);
        // MyBB Forum aktivieren (boardclosed auf 0)
        $conn->query("UPDATE mybb_settings SET value = '0' WHERE name = 'boardclosed'");
    } else {
        // WARTUNG EINSCHALTEN
        file_put_contents($lock_file, 'MAINTENANCE ACTIVE');
        // MyBB Forum schließen (boardclosed auf 1)
        $conn->query("UPDATE mybb_settings SET value = '1' WHERE name = 'boardclosed'");
    }
    
    // Einstellungen im MyBB-Cache aktualisieren (optional, aber empfohlen)
    // Falls MyBB die settings.php nutzt, muss diese ggf. neu generiert werden.
    
    header("Location: ../index.php?maint_toggled=1");
    exit;
}