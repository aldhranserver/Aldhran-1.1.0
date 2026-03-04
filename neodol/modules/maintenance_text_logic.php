<?php
/**
 * MAINTENANCE TEXT LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */
if (!defined('IN_CMS')) { exit; }

// Sicherheit: Admin-Check (Nur Super-Admins Level 5)
if ($userPriv < 5) {
    aldhran_log("SECURITY_ALERT", "Unauthorized access attempt to Maintenance Settings", $currentUserId);
    die("Access Denied");
}

// 1. Speichern via PDO
if (isset($_POST['save_maint_text'])) {
    // Enterprise V2: CSRF Validation
    checkToken($_POST['csrf_token'] ?? '');

    $new_text = trim($_POST['maint_message'] ?? '');
    
    try {
        // Prepared Statement für maximale Sicherheit
        $stmt_upd = $db->prepare("UPDATE settings SET value = ? WHERE setting_key = 'maintenance_text'");
        
        if ($stmt_upd->execute([$new_text])) {
            // Enterprise Logging in den Waschsalon
            aldhran_log("SETTING_CHANGE", "Maintenance text updated", $currentUserId);
            
            header("Location: index.php?p=maintenance_text&msg=success");
            exit;
        }
    } catch (Exception $e) {
        error_log("Maintenance Update Error: " . $e->getMessage());
        die("Ritual failed. Check the logs.");
    }
}

// 2. Laden via PDO
$stmt_load = $db->prepare("SELECT value FROM settings WHERE setting_key = 'maintenance_text' LIMIT 1");
$stmt_load->execute();
$m_data = $stmt_load->fetch();

// Diese Variable befüllt nun dein Textfeld im Admin-Bereich
$current_maint_text = $m_data['value'] ?? "Under Maintenance.";
?>