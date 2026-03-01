<?php
/**
 * THE HERALD - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Combat Analytics
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

// --- 1. DATA COLLECTION (PDO) ---
$q_keeps = "SELECT Name, Realm, ClaimedGuildName FROM keep ORDER BY Realm ASC, Name ASC";
$keeps_stmt = $db->query($q_keeps);
$realm_counts = [1 => 0, 2 => 0, 3 => 0];
$keep_details = $keeps_stmt->fetchAll();

foreach ($keep_details as $k) {
    $rid = (int)$k['Realm'];
    if (isset($realm_counts[$rid])) $realm_counts[$rid]++;
}

// Top 10 Characters (Player Kills Sum & DeathCount)
$q_top = "SELECT Name, Class, Level, Realm, RealmPoints, DeathCount,
          (KillsAlbionPlayers + KillsMidgardPlayers + KillsHiberniaPlayers) AS TotalKills 
          FROM dolcharacters 
          WHERE Level > 0 
          ORDER BY RealmPoints DESC LIMIT 10";

$top_stmt = $db->query($q_top);
$top_chars = $top_stmt->fetchAll();

$r_info = [
    1 => ['name' => 'Albion', 'color' => '#e74c3c', 'icon' => 'fa-chess-rook'],
    2 => ['name' => 'Midgard', 'color' => '#4a90e2', 'icon' => 'fa-hammer'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71', 'icon' => 'fa-leaf']
];
?>

<div class="herald-wrapper">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
        <?php foreach ($r_info as $id => $data): ?>
        <div style="background: rgba(0,0,0,0.4); border-top: 3px solid <?php echo $data['color']; ?>; padding: 25px; text-align: center; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
            <i class="fas <?php echo $data['icon']; ?>" style="font-size: 2.2em; color: <?php echo $data['color']; ?>; margin-bottom: 10px;"></i>
            <h2 style="margin: 0; font-family: 'Cinzel'; letter-spacing: 2px; color: #fff; font-size: 1.1em;"><?php echo strtoupper($data['name']); ?></h2>
            <div style="font-size: 1.8em; color: var(--gold); margin-top: 5px; font-family: 'Cinzel';">
                <?php echo $realm_counts[$id]; ?> <small style="font-size: 0.4em; color: #555; letter-spacing: 1px;">KEEPS</small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px;">
        <div class="admin-box" style="padding: 25px; background: rgba(10,10,10,0.95); border: 1px solid #222;">
            <h3 style="color: var(--gold); border-bottom: 1px solid #222; padding-bottom: 15px; text-transform: uppercase; font-size: 0.85em; font-family: 'Cinzel'; letter-spacing: 2px;">
                <i class="fas fa-medal"></i> Top Combatants
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #444; font-size: 0.7em; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 10px;">Name</th>
                        <th>Class</th>
                        <th style="text-align: center;">K/D Ratio</th>
                        <th style="text-align: right; padding-right: 10px;">Realm Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_chars): foreach ($top_chars as $char): 
                        $kills = (int)$char['TotalKills'];
                        $deaths = (int)$char['DeathCount'];
                        $kd = ($deaths > 0) ? round($kills / $deaths, 2) : (float)$kills;
                        $kd_color = ($kd >= 1.0) ? '#2ecc71' : '#e74c3c';
                    ?>
                        <tr style="border-bottom: 1px solid #111; transition: 0.2s;" onmouseover="this.style.background='rgba(212,175,55,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 15px 10px;">
                                <a href="?p=herald_char&name=<?php echo urlencode($char['Name']); ?>" style="color: #eee; font-weight: bold; text-decoration: none; font-size: 0.95em;">
                                    <?php echo h($char['Name']); ?>
                                </a>
                            </td>
                            <td style="color: #666; font-size: 0.85em;"><?php echo getClassName((int)$char['Class']); ?></td>
                            <td style="text-align: center;">
                                <span style="color: <?php echo $kd_color; ?>; font-family: 'Courier New'; font-weight: bold;">
                                    <?php echo number_format($kd, 2); ?>
                                </span>
                            </td>
                            <td style="text-align: right; color: var(--gold); padding-right: 10px; font-family: 'Courier New'; font-weight: bold;">
                                <?php echo number_format($char['RealmPoints']); ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#444;">The battlefield is currently silent.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-box" style="padding: 25px; background: rgba(10,10,10,0.95); border: 1px solid #222;">
            <h3 style="font-size: 0.85em; color: var(--gold); margin-bottom: 20px; text-transform: uppercase; font-family: 'Cinzel'; letter-spacing: 2px;">Frontier Status</h3>
            <div class="custom-scroll" style="max-height: 550px; overflow-y: auto; padding-right: 10px;">
                <?php if ($keep_details): foreach ($keep_details as $keep): ?>
                    <div style="margin-bottom: 12px; border-left: 3px solid <?php echo $r_info[(int)$keep['Realm']]['color']; ?>; padding-left: 15px; background: rgba(255,255,255,0.02); padding-top: 8px; padding-bottom: 8px;">
                        <div style="color: #ccc; font-weight: bold; font-size: 0.8em;"><?php echo h($keep['Name']); ?></div>
                        <div style="font-size: 0.65em; color: #555; margin-top: 3px; text-transform: uppercase; letter-spacing: 1px;">
                            Held by: <span style="color: #999;"><?php echo (!empty($keep['ClaimedGuildName'])) ? h($keep['ClaimedGuildName']) : 'Unclaimed'; ?></span>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>