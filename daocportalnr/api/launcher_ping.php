<?php
session_start();

// Header for JSON Response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['launcher_active']) && $_POST['launcher_active'] == '1') {
        
        $_SESSION['launcher_present'] = true;
        $_SESSION['last_ping_time'] = time();
        
        echo json_encode(["status" => "success", "msg" => "Launcher recognized"]);
        exit;
    }
}

echo json_encode(["status" => "error", "msg" => "Invalid request"]);