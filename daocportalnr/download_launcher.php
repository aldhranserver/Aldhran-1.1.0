<?php
/**
 * DAoC Portal NR - Launcher Downloader
 * Location: htdocs/daocportalnr/download_launcher.php
 */

// Pfad zur ZIP-Datei (Stelle sicher, dass die Datei dort liegt!)
$file = '../downloads/DAoC_Portal_NR_Launcher.zip';

if (file_exists($file)) {
    // Header setzen, um einen Download zu erzwingen
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    
    // Datei auslesen und an den Browser senden
    readfile($file);
    exit;
} else {
    die("Error: The launcher package is currently not available. Please contact an admin.");
}
?>