<?php
/**
 * THE HERALD - Aldhran Edition
 * Version: 1.0.0 - Standalone (No Forum, K/D & Class Mapping Fix)
 */

// --- DOL CLASS ID MAPPING ---
if (!function_exists('getClassName')) {
    function getClassName($id) {
        $classes = [
            // Albion
            1 => 'Armsman', 2 => 'Cabalist', 3 => 'Cleric', 4 => 'Friar', 5 => 'Infiltrator', 
            6 => 'Mercenary', 7 => 'Minstrel', 8 => 'Paladin', 9 => 'Sorcerer', 10 => 'Theurgist', 
            11 => 'Wizard', 19 => 'Necromancer', 33 => 'Heretic',
            
            // Midgard
            12 => 'Berserker', 13 => 'Healer', 14 => 'Hunter', 15 => 'Runemaster', 16 => 'Shadowblade', 
            17 => 'Skald', 18 => 'Spiritmaster', 19 => 'Thane', 20 => 'Warrior', 21 => 'Shaman', 
            31 => 'Bonedancer', 32 => 'Savage', 34 => 'Valkyrie',
            
            // Hibernia
            22 => 'Bard', 23 => 'Blademaster', 24 => 'Champion', 25 => 'Druid', 26 => 'Eldritch', 
            27 => 'Enchanter', 28 => 'Hero', 29 => 'Mentalist', 30 => 'Nightshade', 31 => 'Ranger', 
            32 => 'Warden', 35 => 'Animist', 36 => 'Valewalker', 39 => 'Bainshee', 40 => 'Vampiir'
        ];
        return $classes[$id] ?? "Unknown ($id)";
    }
}

// --- 1. DATA COLLECTION ---
// Wir nutzen $conn statt $db
$q_keeps = "SELECT Name, Realm, ClaimedGuildName FROM keep ORDER BY Realm ASC, Name ASC";
$keeps_res = $conn->query($q_keeps);
$realm_counts = [1 => 0, 2 => 0, 3 => 0];
$keep_details = [];

if ($keeps_res) {
    while ($k = $keeps_res->fetch_assoc()) {
        $rid = (int)$k['Realm'];
        if (isset($realm_counts[$rid])) $realm_counts[$rid]++;
        $keep_details[] = $k;
    }
}

// Top 10 Characters (Player Kills Sum & DeathCount)
$q_top = "SELECT Name, Class, Level, Realm, RealmPoints, DeathCount,
          (KillsAlbionPlayers + KillsMidgardPlayers + KillsHiberniaPlayers) AS TotalKills 
          FROM dolcharacters 
          WHERE Level > 0 
          ORDER BY RealmPoints DESC LIMIT 10";

$top_res = $conn->query($q_top);

$r_info = [
    1 => ['name' => 'Albion', 'color' => '#e74c3c', 'icon' => 'fa-chess-rook'],
    2 => ['name' => 'Midgard', 'color' => '#4a90e2', 'icon' => 'fa-hammer'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71', 'icon' => 'fa-leaf']
];
?>

<div class="herald-wrapper">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
        <?php foreach ($r_info as $id => $data): ?>
        <div style="background: rgba(0,0,0,0.4); border-top: 3px solid <?php echo $data['color']; ?>; padding: 25px; text-align: center; border-radius: 4px;">
            <i class="fas <?php echo $data['icon']; ?>" style="font-size: 2.2em; color: <?php echo $data['color']; ?>; margin-bottom: 10px;"></i>
            <h2 style="margin: 0; font-family: 'Cinzel'; letter-spacing: 1px; color: #fff;"><?php echo strtoupper($data['name']); ?></h2>
            <div style="font-size: 1.8em; color: var(--gold); margin-top: 5px;"><?php echo $realm_counts[$id]; ?> <small style="font-size: 0.4em; color: #555;">KEEPS</small></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px;">
        <div class="admin-box" style="padding: 25px;">
            <h3 style="color: var(--gold); border-bottom: 1px solid #111; padding-bottom: 15px; text-transform: uppercase; font-size: 0.85em; font-family: 'Cinzel';">
                <i class="fas fa-medal"></i> Top Combatants
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #444; font-size: 0.7em; text-transform: uppercase;">
                        <th style="padding: 10px;">Name</th>
                        <th>Class</th>
                        <th style="text-align: center;">K/D Ratio</th>
                        <th style="text-align: right; padding-right: 10px;">Realm Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_res): while ($char = $top_res->fetch_assoc()): 
                        $kills = (int)$char['TotalKills'];
                        $deaths = (int)$char['DeathCount'];
                        $kd = ($deaths > 0) ? round($kills / $deaths, 2) : $kills;
                        $kd_color = ($kd >= 1.0) ? '#2ecc71' : '#e74c3c';
                    ?>
                        <tr style="border-bottom: 1px solid #0a0a0a;">
                            <td style="padding: 15px 10px;">
                                <a href="?p=herald_char&name=<?php echo urlencode($char['Name']); ?>" style="color: #eee; font-weight: bold; text-decoration: none; font-size: 0.95em;">
                                    <?php echo htmlspecialchars($char['Name']); ?>
                                </a>
                            </td>
                            <td style="color: #777; font-size: 0.85em;"><?php echo getClassName((int)$char['Class']); ?></td>
                            <td style="text-align: center;">
                                <span style="color: <?php echo $kd_color; ?>; font-family: 'Courier New'; font-weight: bold;">
                                    <?php echo number_format($kd, 2); ?>
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--gold); padding-right: 10px; font-family: 'Courier New';">
                                <?php echo number_format($char['RealmPoints']); ?>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-box" style="padding: 25px;">
            <h3 style="font-size: 0.85em; color: var(--gold); margin-bottom: 15px; text-transform: uppercase; font-family: 'Cinzel';">Frontier Status</h3>
            <div class="custom-scroll" style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
                <?php foreach ($keep_details as $keep): ?>
                    <div style="margin-bottom: 15px; border-left: 3px solid <?php echo $r_info[(int)$keep['Realm']]['color']; ?>; padding-left: 12px; background: rgba(255,255,255,0.01); padding-top: 5px; padding-bottom: 5px;">
                        <div style="color: #eee; font-weight: bold; font-size: 0.85em;"><?php echo htmlspecialchars($keep['Name']); ?></div>
                        <div style="font-size: 0.7em; color: #555; margin-top: 2px; text-transform: uppercase;">
                            Held by: <span style="color: #888;"><?php echo (!empty($keep['ClaimedGuildName'])) ? htmlspecialchars($keep['ClaimedGuildName']) : 'Unclaimed'; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>