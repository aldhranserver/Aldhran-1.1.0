<?php
/**
 * HARDCORE DEBUG - DAoC Portal NR
 */
require_once('../../includes/db.php'); 

$logFile = 'debug_ping_log.txt';
$ip = $_SERVER['REMOTE_ADDR'];
$time = date('Y-m-d H:i:s');
$input = file_get_contents('php://input');

// JEDEN Request protokollieren
file_put_contents($logFile, "[$time] IP: $ip | Method: " . $_SERVER['REQUEST_METHOD'] . " | Data: $input" . PHP_EOL, FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $active = $_POST['launcher_active'] ?? '0';
    if ($active == '1') {
        $stmt = $db->prepare("REPLACE INTO launcher_ips (ip_address, last_ping, is_active) VALUES (?, NOW(), 1)");
        $status = $stmt->execute([$ip]);
        file_put_contents($logFile, "[$time] DB-Update Erfolg: " . ($status ? 'JA' : 'NEIN') . PHP_EOL, FILE_APPEND);
        echo json_encode(["status" => "success"]);
        exit;
    }
}
echo json_encode(["status" => "error", "msg" => "No POST or active=0"]);