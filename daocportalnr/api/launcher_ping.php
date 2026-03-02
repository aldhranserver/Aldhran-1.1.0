<?php
/**
 * DAoC Portal NR - Launcher Handshake API
 * Location: htdocs/daocportalnr/api/launcher_ping.php
 */

// Dieselbe Session-Konfiguration wie in der index.php
if (session_status() === PHP_SESSION_NONE) { 
    session_set_cookie_params(0, '/'); 
    session_start(); 
}

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