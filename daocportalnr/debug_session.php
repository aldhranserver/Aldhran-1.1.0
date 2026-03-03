<?php
/**
 * Aldhran Session Debugger
 * Location: htdocs/daocportalnr/debug_session.php
 */

// WICHTIG: Die exakt gleiche Session-Konfiguration
session_set_cookie_params(0, '/'); 
session_start();

echo "<h2>Aldhran Launcher Debug</h2>";
echo "<b>Aktuelle Session-ID:</b> " . session_id() . "<br><br>";

if (isset($_SESSION['launcher_present']) && $_SESSION['launcher_present'] === true) {
    echo "<span style='color:green; font-weight:bold;'>✓ LAUNCHER ERKANNT!</span><br>";
    echo "Letzter Ping: " . date("H:i:s", $_SESSION['last_ping_time']) . "<br>";
} else {
    echo "<span style='color:red; font-weight:bold;'>✗ LAUNCHER NICHT GEFUNDEN IN DIESER SESSION.</span><br>";
}

echo "<h3>Vollständiger Session-Inhalt:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<a href='index.php'>Zurück zur Index</a> | ";
echo "<a href='debug_session.php'>Seite aktualisieren</a>";