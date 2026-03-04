<?php
/**
 * DAoC Portal NR - Live Player Count Endpoint
 */
require_once('../includes/db.php');
header('Content-Type: application/json');
header('Cache-Control: no-store');

$online = 0;
try {
    $online = (int)$db->query("SELECT COUNT(*) FROM account WHERE LastLogin > NOW() - INTERVAL 10 MINUTE")->fetchColumn();
} catch (Exception $e) {
    error_log("[DAoCPortalNR] player_counts.php Fehler: " . $e->getMessage());
}

$result = [];
try {
    $stmt = $db->query("SELECT id FROM daoc_servers WHERE is_active = 1");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $result[$id] = $online;
    }
} catch (Exception $e) {
    error_log("[DAoCPortalNR] player_counts.php server-list Fehler: " . $e->getMessage());
}

echo json_encode($result);