<?php
/**
 * Aldhran Logout System
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Alle Session-Variablen löschen
$_SESSION = array();

// 2. Das Session-Cookie im Browser löschen
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Die Session auf dem Server zerstören
session_destroy();

// 4. Zurück zur Startseite leiten
header("Location: index.php?msg=logged_out");
exit;
?>