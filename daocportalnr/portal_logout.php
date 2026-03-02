<?php
/**
 * DAoC Portal NR - Logout
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Nur Portal-Sessions löschen
unset($_SESSION['portal_user_id']);
unset($_SESSION['portal_username']);

header("Location: index.php");
exit;