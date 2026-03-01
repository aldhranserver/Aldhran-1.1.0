<?php
if (!defined('IN_CMS')) exit;

$token_url = "https://discord.com/api/oauth2/token";
$user_url = "https://discord.com/api/users/@me";

$data = [
    'client_id' => 'DEINE_CLIENT_ID',
    'client_secret' => 'DEIN_CLIENT_SECRET',
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => SITE_URL . '/index.php?p=discord_callback',
    'scope' => 'identify'
];

// 1. Token vom Discord holen
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
        $discord_avatar = $user_data['avatar'];

        // 3. In der DB verknüpfen oder Login durchführen
        // Hier prüfen wir gleich, ob die discord_id schon existiert
        $check = $conn->query("SELECT id, username FROM users WHERE discord_id = '$discord_id'");
        
        if ($check->num_rows > 0) {
            $user = $check->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php?p=home&msg=discord_welcome");
        } else {
            // Noch nicht verknüpft? Dann ab zum Linking oder Registrierung
            $_SESSION['temp_discord_id'] = $discord_id;
            header("Location: index.php?p=register&mode=discord");
        }
    }
} else {
    die("Discord Handshake fehlgeschlagen.");
}
curl_close($ch);
exit;