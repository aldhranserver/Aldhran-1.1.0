<?php
/**
 * style.php - Aldhran CMS Dynamic CSS Loader
 * Version: 2.1.3 - FINAL MIME & BUFFER FIX
 */

// 1. Puffer starten und säubern (verhindert 'Headers already sent' Fehler)
ob_start();
ob_clean();

// 2. Absolut korrekter Header (WICHTIG: Gleichheitszeichen bei charset!)
header("Content-Type: text/css; charset=UTF-8");
header("X-Content-Type-Options: nosniff");

require_once('includes/db.php'); 

$module = $_GET['module'] ?? 'main';

try {
    // CORE CSS
    $stmt_main = $db->prepare("SELECT css_content FROM aldhran_styles WHERE module_key = 'main' AND is_active = 1");
    $stmt_main->execute();
    $row_main = $stmt_main->fetch();

    if ($row_main) {
        echo "/* --- CORE --- */\n" . $row_main['css_content'] . "\n\n";
    }

    // MODUL CSS (Dein 'um' Key)
    if ($module !== 'main' && $module !== 'home') {
        $stmt_sub = $db->prepare("SELECT css_content FROM aldhran_styles WHERE module_key = ? AND is_active = 1");
        $stmt_sub->execute([$module]);
        $row_sub = $stmt_sub->fetch();
        
        if ($row_sub) {
            echo "/* --- MODULE: " . strtoupper(htmlspecialchars($module)) . " --- */\n";
            echo "\n" . $row_sub['css_content'] . "\n";
        } else {
            echo "/* --- DEBUG: Module '" . htmlspecialchars($module) . "' not found --- */";
        }
    }

} catch (PDOException $e) {
    echo "/* Database Error: " . $e->getMessage() . " */";
}

// 3. Puffer ausgeben
ob_end_flush();
?>