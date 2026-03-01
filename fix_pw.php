<?php
/**
 * ALDHRAN EMERGENCY PASSWORD RESET
 * Setzt das Passwort für 'Seraltos' auf 'test33'
 */
require_once('includes/db.php');

// 1. Das neue Passwort definieren
$new_password = "test33";

// 2. Den Hash exakt so erzeugen, wie PHP ihn will
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 3. Den User in der Datenbank updaten (Seraltos)
$username = "Seraltos";
$stmt = $conn->prepare("UPDATE users SET password = ?, password_hash = ? WHERE username = ?");
$stmt->bind_param("sss", $hashed_password, $hashed_password, $username);

if ($stmt->execute()) {
    echo "<h2 style='color:green;'>Erfolg!</h2>";
    echo "Das Passwort für <b>$username</b> wurde auf <b>$new_password</b> gesetzt.<br>";
    echo "Der erzeugte Hash war: <code>$hashed_password</code><br><br>";
    echo "<a href='index.php?p=login&unlock=Aldhran2026'>Hier klicken, um Sperre zu lösen und einzuloggen</a>";
} else {
    echo "<h2 style='color:red;'>Fehler!</h2>" . $conn->error;
}
?>