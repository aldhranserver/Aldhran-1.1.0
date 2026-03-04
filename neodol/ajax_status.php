<?php
// ajax_status.php
require_once('header.php'); // Nutzt die bestehende Logik für $is_online und $online_count

$response = [
    'online' => $is_online,
    'count' => $online_count
];

header('Content-Type: application/json');
echo json_encode($response);
?>