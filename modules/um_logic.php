<?php
/**
 * UM LOGIC - Nexus Controller V1.2
 */
if (!isset($can_edit) || !$can_edit) {
    die("Nexus Logic: Access Denied.");
}

// 1. POST Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['um_action'])) {
    require_once('um_sync_worker.php');
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
        return $texts[$level] ?? "Unknown";
    }
}
?>