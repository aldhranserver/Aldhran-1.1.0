<?php
/**
 * UM LOGIC - Nexus Controller V2.1
 * Version: 2.1.0 - SECURITY: PDO Migration | AJAX: Integrated Fragment Switch
 */
if (!isset($can_edit) || !$can_edit) {
    die("Nexus Logic: Access Denied.");
}

// Wir nutzen das globale PDO Objekt $db aus der db.php
global $db;

// 1. POST Aktionen verarbeiten (Synchronisation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['um_action'])) {
    // CSRF Schutz für Admin-Eingriffe
    checkToken($_POST['csrf_token'] ?? '');
    require_once('um_sync_worker.php');
}

// 2. Daten für die Anzeige laden
$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$u_data = null;

if ($target_id > 0) {
    // Abfrage via PDO Prepared Statement
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$target_id]);
    $u_data = $stmt->fetch();

    // --- NEU: AJAX-WEICHE ---
    // Wenn die Anfrage via JavaScript (AJAX) kommt, laden wir NUR den Editor
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        if ($u_data) {
            // Pfad zur Editor-View (bitte prüfen, ob der Dateiname stimmt)
            include('um_editor_view.php'); 
        } else {
            echo "<p style='color:red; padding:20px;'>Soul not found in Archives.</p>";
        }
        exit; // Beendet das Script, damit kein Header/Footer geladen wird
    }
}

/**
 * Hilfs-Funktion: Status Text
 */
if (!function_exists('getStandingText')) {
    function getStandingText($level) {
        $texts = [
            0 => "Good", 
            1 => "Warning I", 
            2 => "Warning II", 
            3 => "Restricted", 
            4 => "Suspended", 
            5 => "Banned"
        ];
        return $texts[(int)$level] ?? "Unknown";
    }
}
?>