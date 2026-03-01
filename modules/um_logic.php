<?php
/**
 * UM LOGIC - Nexus Controller V2.1.1
 * Version: 2.1.1 - FIX: AJAX Gatekeeper extended for "Add User" form
 */
if (!isset($can_edit) || !$can_edit) {
    die("Nexus Logic: Access Denied.");
}

// Wir nutzen das globale PDO Objekt $db aus der db.php
global $db;

// 1. POST Aktionen verarbeiten (Synchronisation & AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Wir prüfen auf JEDEN Trigger, den das UI senden kann
    $is_um_call = isset($_POST['um_action']) || 
                  isset($_POST['um_ajax_search']) || 
                  isset($_POST['um_load_cat']) || 
                  isset($_POST['um_ajax_get_editor']) || 
                  isset($_POST['um_ajax_get_add_form']);

    if ($is_um_call) {
        // CSRF Schutz: Verhindert unbefugte Eingriffe von außen
        if (function_exists('checkToken')) {
            // Wir lassen checkToken nur laufen, wenn ein Token erwartet wird
            // (Bei reinen Lade-Abfragen ist er optional, beim Speichern Pflicht)
            if (isset($_POST['csrf_token'])) {
                checkToken($_POST['csrf_token']);
            }
        }
        
        // Jetzt lassen wir den Worker die schwere Arbeit machen
        require_once('um_sync_worker.php');
    }
}

// 2. Initialer Zustand
$u_data = null;

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