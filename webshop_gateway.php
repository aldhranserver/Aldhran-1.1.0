<?php
/**
 * ALDHRAN CMS - WEBSHOP GATEWAY
 * Schnittstelle für den DOL GameServer Poller
 */

// Zentralen Datenbank-Bootstrap laden
require_once('includes/db.php'); 

// Das Secret muss exakt mit deinem C#-Code übereinstimmen!
$api_token = "ALDHRAN_SUPER_SECRET"; 

// 1. Sicherheits-Check: Token-Validierung
if (!isset($_GET['token']) || $_GET['token'] !== $api_token) {
    header('HTTP/1.1 403 Forbidden');
    die("Access Denied: Invalid Token");
}

// 2. GET: Offene Bestellungen für den Server auslesen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Wir nutzen die global verfügbare $conn aus der db.php
    $sql = "SELECT id, player_name AS player, item_template_id AS item_template, count 
            FROM webshop_orders 
            WHERE delivered = 0";
            
    $result = $conn->query($sql);
    $orders = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($orders);
    exit;
}

// 3. POST: Bestätigung vom Server (Bestellung erfolgreich im Inventar gelandet)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['order_id'])) {
        $order_id = (int)$data['order_id'];
        
        // Markierung als 'ausgeliefert'
        $conn->query("UPDATE webshop_orders SET delivered = 1 WHERE id = $order_id");
        
        header('Content-Type: application/json');
        echo json_encode(["status" => "success", "order_id" => $order_id]);
    }
    exit;
}