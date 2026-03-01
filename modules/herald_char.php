<?php
/**
 * CHARACTER HERALD DETAILS - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Prepared Statements
 */

// Name holen und säubern
$char_name = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($char_name)) {
    echo "<div class='admin-box'>No character specified.</div>";
    return;
}

// 1. CHARACTER DATA via PDO abrufen
$stmt = $db->prepare("
    SELECT c.*, g.Name as GuildName 
    FROM dolcharacters c 
    LEFT JOIN guild g ON c.GuildID = g.GuildID 
    WHERE c.Name = ?
");
$stmt->execute([$char_name]);
$c = $stmt->fetch();

if (!$c) {
    echo "<div class='admin-box'>Character '" . h($char_name) . "' not found in the chronicles.</div>";
    return;
}

// Realm Info Mapping
$r_info = [
    1 => ['name' => 'Albion', 'color' => '#4a90e2'],
    2 => ['name' => 'Midgard', 'color' => '#e74c3c'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71']
];
$r_color = $r_info[(int)$c['Realm']]['color'] ?? '#555';
?>

<div class="admin-container">
    <a href="?p=herald" style="color: #555; text-decoration: none; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">
        <i class="fas fa-chevron-left"></i> Back to Herald
    </a>

    <div class="admin-box" style="margin-top: 15px; border-top: 3px solid <?php echo $r_color; ?>; padding: 40px; background: rgba(10,10,10,0.95);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #111; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; font-family: 'Cinzel'; color: #eee; font-size: 2.5em;"><?php echo h($c['Name']); ?></h1>
                <div style="color: <?php echo $r_color; ?>; font-weight: bold; font-size: 1em; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px;">
                    Level <?php echo (int)$c['Level']; ?> <?php echo h($c['Class']); ?> 
                </div>
                <div style="color: #555; font-size: 0.85em; margin-top: 10px;">
                    <i class="fas fa-users" style="margin-right: 5px;"></i> Guild: <span style="color: #888;"><?php echo h($c['GuildName'] ?: 'None'); ?></span>
                </div>
            </div>
            
            <div style="text-align: right;">
                <div style="font-size: 0.7em; color: #555; text-transform: uppercase; letter-spacing: 2px;">Realm Rank</div>
                <div style="font-size: 3em; color: var(--gold); font-family: 'Cinzel'; line-height: 1;">
                    <?php echo h($c['Rank'] ?? '1L0'); ?>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: rgba(0,0,0,0.2); padding: 20px; border: 1px solid #0a0a0a; border-radius: 4px;">
                <div style="font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Total Realm Points</div>
                <div style="font-size: 1.4em; color: #ccc; font-family: 'Cinzel';"><?php echo number_format($c['RealmPoints']); ?></div>
            </div>

            <div style="background: rgba(0,0,0,0.2); padding: 20px; border: 1px solid #0a0a0a; border-radius: 4px;">
                <div style="font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Bounty Points</div>
                <div style="font-size: 1.4em; color: #ccc; font-family: 'Cinzel';"><?php echo number_format($c['BountyPoints'] ?? 0); ?></div>
            </div>

            <div style="background: rgba(0,0,0,0.2); padding: 20px; border: 1px solid #0a0a0a; border-radius: 4px;">
                <div style="font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Total Kills</div>
                <div style="font-size: 1.4em; color: #ccc; font-family: 'Cinzel';"><?php echo number_format($c['KillsTotals'] ?? 0); ?></div>
            </div>

            <div style="background: rgba(0,0,0,0.2); padding: 20px; border: 1px solid #0a0a0a; border-radius: 4px;">
                <div style="font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 1px;">Total Deaths</div>
                <div style="font-size: 1.4em; color: #ccc; font-family: 'Cinzel';"><?php echo number_format($c['DeathsTotals'] ?? 0); ?></div>
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 0.7em; color: #333; text-align: center; border-top: 1px solid #0a0a0a; padding-top: 20px; text-transform: uppercase; letter-spacing: 1px;">
            Chronicled data synchronized with Aldhran Gameserver.
        </div>
    </div>
</div>