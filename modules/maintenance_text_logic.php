<?php
if ($userPriv < 5) die("Access Denied");

// 1. Speichern (Update des Wertes in der Spalte 'value' für den richtigen Key)
if (isset($_POST['save_maint_text'])) {
    $new_text = mysqli_real_escape_string($conn, $_POST['maint_message']);
    
    // Wir aktualisieren die Spalte 'value' dort, wo der 'setting_key' passt
    $conn->query("UPDATE `settings` SET `value` = '$new_text' WHERE `setting_key` = 'maintenance_text'");
    
    header("Location: index.php?p=maintenance_text&msg=success");
    exit;
}

// 2. Laden (Abrufen der Spalte 'value' für den Key 'maintenance_text')
$res = $conn->query("SELECT `value` FROM `settings` WHERE `setting_key` = 'maintenance_text' LIMIT 1");
$m_data = $res->fetch_assoc();

// Diese Variable befüllt nun dein Textfeld im Admin-Bereich
$current_maint_text = $m_data['value'] ?? "";
?>