<?php
/**
 * DAoC Portal NR - API Update Endpoint
 * Location: htdocs/daocportalnr/api/update.php
 */
require_once('../../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Access Denied");
}

$key  = $_POST['api_key'] ?? '';
$pop  = (int)($_POST['players'] ?? 0);
$vers = $_POST['version'] ?? '1.0';

if (empty($key)) {
    die("Missing API Key");
}

// Suche Server zum Key
$stmt = $db->prepare("SELECT id, server_name FROM daoc_servers WHERE api_key = ?");
$stmt->execute([$key]);
$server = $stmt->fetch();

if ($server) {
    // Update der Live-Daten
    $update = $db->prepare("UPDATE daoc_servers SET pop_count = ?, is_online = 1, last_check = NOW() WHERE id = ?");
    $update->execute([$pop, $server['id']]);
    
    echo "SUCCESS: Data received for " . $server['server_name'];
} else {
    echo "ERROR: Invalid API Key";
}