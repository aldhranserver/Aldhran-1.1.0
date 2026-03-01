<?php
/**
 * ALDHRAN CMS - WEBSHOP GATEWAY
 * Version: 2.0.0 - SECURITY: PDO Migration & API Hardening
 */

// Zentralen Datenbank-Bootstrap laden (Nutzt jetzt $db statt $conn)
require_once('includes/db.php'); 

// Das Secret muss exakt mit deinem C#-Code übereinstimmen!
$api_token = "ALDHRAN_SUPER_SECRET"; 

// 1. Sicherheits-Check: Token-Validierung
if (!isset($_GET['token']) || $_GET['token'] !== $api_token) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    die(json_encode(["error" => "Access Denied: Invalid Token"]));
}

// 2. GET: Offene Bestellungen für den Server auslesen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Wir nutzen die globale $db (PDO)
        $sql = "SELECT id, player_name AS player, item_template_id AS item_template, count 
                FROM webshop_orders 
                WHERE delivered = 0";
        
        $stmt = $db->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($orders);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["error" => "Database Query Failed"]);
    }
    exit;
}

// 3. POST: Bestätigung vom Server (Bestellung erfolgreich im Inventar gelandet)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['order_id'])) {
        $order_id = (int)$data['order_id'];
        
        try {
            // Markierung als 'ausgeliefert' via Prepared Statement
            $stmt = $db->prepare("UPDATE webshop_orders SET delivered = 1 WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // Optional: Logging im Waschsalon
            aldhran_log("WEBSHOP_DELIVERY", "Order #$order_id delivered to game server", 0);
            
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "order_id" => $order_id]);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(["status" => "error", "message" => "Update failed"]);
        }
    }
    exit;
}