<?php
/**
 * GLOBAL MAINTENANCE TOGGLE - Aldhran Enterprise
 * Version: 2.1.0 - REMOVED: MyBB Hooks | ADDED: PDO & CSRF
 */
require_once('../includes/db.php');

// Sicherheit: Nur Super-Admins (Level 5)
if (($_SESSION['priv_level'] ?? 0) < 5) {
    aldhran_log("SECURITY_ALERT", "Unauthorized maintenance toggle attempt", $_SESSION['user_id'] ?? 0);
    die("Access Denied");
}

if (isset($_POST['toggle_maint'])) {
    // Enterprise V2: CSRF Validation
    checkToken($_POST['csrf_token'] ?? '');

    $lock_file = '../maintenance.lock';
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    $action_status = "";

    try {
        if (file_exists($lock_file)) {
            // --- WARTUNG AUSSCHALTEN ---
            if (@unlink($lock_file)) {
                $action_status = "DEACTIVATED";
            }
        } else {
            // --- WARTUNG EINSCHALTEN ---
            $content = "MAINTENANCE ACTIVE\nStarted by: " . ($_SESSION['username'] ?? 'Unknown Admin') . "\nID: $admin_id\nTime: " . date('Y-m-d H:i:s');
            
            if (file_put_contents($lock_file, $content)) {
                $action_status = "ACTIVATED";
            }
        }

        // Logging in den "Waschsalon" (aldhran_logs)
        if (!empty($action_status)) {
            aldhran_log("MAINTENANCE_TOGGLE", "Global maintenance mode $action_status", $admin_id);
        }

        header("Location: ../index.php?p=maintenance_text&msg=toggled");
        exit;

    } catch (Exception $e) {
        error_log("Maintenance Toggle Error: " . $e->getMessage());
        die("The ritual of shifting realms failed. Check the logs.");
    }
}