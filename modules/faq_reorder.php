<?php
/**
 * FAQ AJAX REORDER ENDPOINT
 * Version: 0.9.5 - Battler Edition
 */

// Einfache Sicherheitsprüfung (Sollte in deiner index.php/Session verankert sein)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = $_POST['order']; // Array von IDs in neuer Reihenfolge
    
    foreach ($order as $index => $id) {
        $id = (int)$id;
        $sort = (int)$index;
        $db->query("UPDATE faq SET sort_order = $sort WHERE id = $id");
    }
    echo "Success";
    exit;
}