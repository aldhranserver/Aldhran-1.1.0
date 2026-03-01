<?php
/**
 * GUILD HERALD DETAILS
 * Version: 0.8.9 - Guild Roster & Stats
 */

$guild_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($guild_id === 0) {
    echo "<div class='admin-box'>No guild specified.</div>";
    return;
}

// 1. Gilden-Basisdaten abrufen
$g_res = $db->query("SELECT * FROM guild WHERE GuildID = $guild_id");

if ($g_res->num_rows === 0) {
    echo "<div class='admin-box'>Guild not found in the chronicles.</div>";
    return;
}

$g = $g_res->fetch_assoc();

// 2. Mitgliederliste abrufen
$m_res = $db->query("
    SELECT Name, Class, Level, RealmPoints, Rank 
    FROM dolcharacters 
    WHERE GuildID = $guild_id 
    ORDER BY RealmPoints DESC
");

$realm_id = (int)($g['Realm'] ?? 0);
$r_info = [
    1 => ['name' => 'Albion', 'color' => '#4a90e2'],
    2 => ['name' => 'Midgard', 'color' => '#e74c3c'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71']
];
$r_color = $r_info[$realm_id]['color'] ?? '#555';
?>

<div class="admin-container">
    <a href="?p=herald" style="color: #555; text-decoration: none; font-size: 0.8em; text-transform: uppercase;">
        <i class="fas fa-chevron-left"></i> Back to Herald
    </a>

    <div class="admin-box" style="margin-top: 15px; border-top: 3px solid <?php echo $r_color; ?>;">
        <div style="text-align: center; border-bottom: 1px solid #111; padding-bottom: 25px; margin-bottom: 25px;">
            <h1 style="margin: 0; font-family: 'Cinzel'; color: var(--gold); font-size: 2.5em;">
                &lt; <?php echo htmlspecialchars($g['Name']); ?> &gt;
            </h1>
            <div style="color: #555; text-transform: uppercase; letter-spacing: 2px; font-size: 0.9em; margin-top: 5px;">
                Bound to the Realm of <?php echo $r_info[$realm_id]['name'] ?? 'Unknown'; ?>
            </div>
        </div>

        <h3 style="color: #eee; font-size: 0.9em; text-transform: uppercase; margin-bottom: 15px;">
            <i class="fas fa-users"></i> Guild Roster (<?php echo $m_res->num_rows; ?> Members)
        </h3>
        
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
            <thead>
                <tr style="text-align: left; color: #444; font-size: 0.75em; text-transform: uppercase; border-bottom: 1px solid #111;">
                    <th style="padding: 10px;">Name</th>
                    <th>Class</th>
                    <th>Level</th>
                    <th style="text-align: right; padding-right: 10px;">Realm Points</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $m_res->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #0a0a0a; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 12px 10px;">
                        <a href="?p=herald_char&name=<?php echo urlencode($m['Name']); ?>" style="color: #ccc; font-weight: bold; text-decoration: none;">
                            <?php echo htmlspecialchars($m['Name']); ?>
                        </a>
                    </td>
                    <td style="color: #666;"><?php echo $m['Class']; ?></td>
                    <td style="color: #666;"><?php echo $m['Level']; ?></td>
                    <td style="text-align: right; padding-right: 10px; color: var(--gold); font-family: 'Courier New';">
                        <?php echo number_format($m['RealmPoints']); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>