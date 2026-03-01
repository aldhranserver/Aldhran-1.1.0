<?php
/**
 * THE HERALD - Aldhran Edition
 * Version: 0.8.11 - SQL Safeguard & Debug
 */

// --- DEBUG: SQL Fehler anzeigen ---
function checkQuery($db, $result, $query) {
    if (!$result) {
        die("<div class='admin-box' style='border-color: red;'>
                <h3 style='color:red;'>SQL Error in Herald</h3>
                <p>Query: <code>$query</code></p>
                <p>Error: " . $db->error . "</p>
             </div>");
    }
    return $result;
}

// --- 1. REALM WAR STATUS ---
// Wir prüfen erst, ob die Spalten in 'keep' existieren
$q_keeps = "SELECT * FROM keep LIMIT 1";
$res_keeps = $db->query($q_keeps);

if ($res_keeps) {
    $row = $res_keeps->fetch_assoc();
    $hasGuild = isset($row['GuildID']) ? "k.GuildID" : "NULL as GuildID";
    
    $q_status = "
        SELECT k.Name, k.Realm, g.Name as GuildName, $hasGuild
        FROM keep k 
        LEFT JOIN guild g ON k.GuildID = g.GuildID 
        ORDER BY k.Realm ASC, k.Name ASC";
    
    $keeps_res = checkQuery($db, $db->query($q_status), $q_status);
} else {
    die("<div class='admin-box'>Table 'keep' not found. Please check your DOL database.</div>");
}

$realm_counts = [1 => 0, 2 => 0, 3 => 0];
$keep_details = [];
while ($k = $keeps_res->fetch_assoc()) {
    $r_id = (int)$k['Realm'];
    if (isset($realm_counts[$r_id])) $realm_counts[$r_id]++;
    $keep_details[] = $k;
}

// --- 2. RANKING LOGIC (TOP 10) ---
// Hier prüfen wir die Namen der Punkte-Spalten (oft 'RealmPoints' oder 'Experience')
$q_top = "SELECT Name, Class, Level, Realm, RealmPoints FROM dolcharacters WHERE Level > 0 ORDER BY RealmPoints DESC LIMIT 10";
$top_res = checkQuery($db, $db->query($q_top), $q_top);

$r_info = [
    1 => ['name' => 'Albion', 'color' => '#e74c3c', 'icon' => 'fa-chess-rook'],
    2 => ['name' => 'Midgard', 'color' => '4a90e2', 'icon' => 'fa-hammer'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71', 'icon' => 'fa-leaf']
];
?>

<div class="admin-container">
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
        <?php foreach ($r_info as $id => $data): ?>
        <div style="background: rgba(0,0,0,0.4); border-top: 3px solid <?php echo $data['color']; ?>; padding: 25px; text-align: center;">
            <i class="fas <?php echo $data['icon']; ?>" style="font-size: 2.5em; color: <?php echo $data['color']; ?>; margin-bottom: 10px;"></i>
            <h2 style="margin: 0; font-family: 'Cinzel';"><?php echo strtoupper($data['name']); ?></h2>
            <div style="font-size: 2em; color: #fff; margin: 10px 0;"><?php echo $realm_counts[$id]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        
        <div class="admin-box">
            <h3 style="color: var(--gold); border-bottom: 1px solid #111; padding-bottom: 15px;">TOP COMBATANTS</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #444; font-size: 0.7em;">
                        <th style="padding: 10px;">NAME</th>
                        <th>CLASS</th>
                        <th>REALM</th>
                        <th style="text-align: right;">POINTS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($char = $top_res->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #0a0a0a;">
                        <td style="padding: 15px 10px;">
                            <a href="?p=herald_char&name=<?php echo urlencode($char['Name']); ?>" style="color: #eee; font-weight: bold; text-decoration: none;">
                                <?php echo htmlspecialchars($char['Name']); ?>
                            </a>
                        </td>
                        <td style="color: #777;"><?php echo $char['Class']; ?></td>
                        <td style="color: <?php echo $r_info[(int)$char['Realm']]['color']; ?>;"><?php echo $r_info[(int)$char['Realm']]['name']; ?></td>
                        <td style="text-align: right; color: var(--gold);"><?php echo number_format($char['RealmPoints']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-box">
            <h3 style="font-size: 0.8em; color: var(--gold); margin-bottom: 15px;">FRONTIER STATUS</h3>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($keep_details as $keep): ?>
                    <div style="margin-bottom: 12px; font-size: 0.85em; border-left: 2px solid <?php echo $r_info[(int)$keep['Realm']]['color']; ?>; padding-left: 10px;">
                        <div style="color: #ccc;"><?php echo htmlspecialchars($keep['Name']); ?></div>
                        <div style="font-size: 0.75em; color: #555;">
                            Held by: <?php echo htmlspecialchars($keep['GuildName'] ?? 'No Guild'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>