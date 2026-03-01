<?php
/**
 * DISCORD OAUTH2 LOGIC - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Audit Logging
 */
if (!defined('IN_CMS')) exit;

$token_url = "https://discord.com/api/oauth2/token";
$user_url = "https://discord.com/api/users/@me";

// Die Daten kommen aus deiner Discord Developer Console
$data = [
    'client_id' => 'DEINE_CLIENT_ID',
    'client_secret' => 'DEIN_CLIENT_SECRET',
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'] ?? '',
    'redirect_uri' => SITE_URL . '/index.php?p=discord_callback',
    'scope' => 'identify'
];

if (empty($data['code'])) {
    die("Ungültiger Discord-Request.");
}

// 1. Token vom Discord holen via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);

if (isset($response['access_token'])) {
    $access_token = $response['access_token'];

    // 2. User-Infos mit dem Token abrufen
    $header = array("Authorization: Bearer $access_token");
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $user_data = json_decode(curl_exec($ch), true);

    if (isset($user_data['id'])) {
        $discord_id = $user_data['id'];
        $discord_name = $user_data['username'];

        // 3. In der DB prüfen via PDO
        $stmt_check = $db->prepare("SELECT id, username, priv_level, standing FROM users WHERE discord_id = ?");
        $stmt_check->execute([$discord_id]);
        $user = $stmt_check->fetch();
        
        if ($user) {
            // Check ob der User permanent gesperrt ist (Standing 5)
            if ((int)$user['standing'] >= 5) {
                aldhran_log("DISCORD_LOGIN_BANNED", "Banned user tried Discord login: $discord_name");
                die("Dein Zugang zu Aldhran wurde permanent gesperrt.");
            }

            // LOGIN DURCHFÜHREN
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['priv_level'] = (int)$user['priv_level'];
            $_SESSION['user_standing'] = (int)$user['standing'];

            // ENTERPRISE LOGGING
            aldhran_log("DISCORD_LOGIN_SUCCESS", "User logged in via Discord", $user['id']);

            header("Location: index.php?p=home&msg=discord_welcome");
            exit;
        } else {
            // Noch nicht verknüpft? Dann ab zur Registrierung
            $_SESSION['temp_discord_id'] = $discord_id;
            $_SESSION['temp_discord_name'] = $discord_name;
            
            aldhran_log("DISCORD_AUTH_NEW", "Discord ID recognized, proceeding to linking");
            header("Location: index.php?p=register&mode=discord");
            exit;
        }
    }
} else {
    aldhran_log("DISCORD_HANDSHAKE_FAIL", "OAuth2 Token request failed");
    die("Discord Handshake fehlgeschlagen.");
}
curl_close($ch);
exit;