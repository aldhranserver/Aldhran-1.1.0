<?php 
/**
 * UM LOGIC - Nexus Controller V2.2.0
 */
if (!isset($can_edit) || !$can_edit) {
    die("Nexus Logic: Access Denied.");
}

global $db;

// 1. POST Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $is_um_call = isset($_POST['um_action']) || 
                  isset($_POST['um_ajax_search']) || 
                  isset($_POST['um_load_cat']) || 
                  isset($_POST['um_ajax_get_editor']) || 
                  isset($_POST['um_ajax_get_add_form']);

    if ($is_um_call) {
        if (function_exists('checkToken')) {
            if (isset($_POST['csrf_token'])) {
                checkToken($_POST['csrf_token']);
            }
        }
        require_once('um_sync_worker.php');
    }
}

$u_data = null;

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