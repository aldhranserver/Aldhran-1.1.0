<?php
if (!isset($db)) require_once('includes/db.php');

$item_id = isset($_GET['id']) ? mysqli_real_escape_string($db, $_GET['id']) : '';

if (empty($item_id)) {
    echo "<div class='admin-container'><p>Item not found in chronicles.</p></div>";
    return;
}

// Holen der Item-Daten inklusive aller Stats
$item = $db->query("SELECT * FROM itemtemplate WHERE Id_nb = '$item_id'")->fetch_assoc();

if (!$item) {
    echo "<div class='admin-container'><p>The void has consumed this item.</p></div>";
    return;
}

/**
 * Hilfsfunktion für Boni (DOL-Format)
 */
function renderBonus($type, $value) {
    if ($value <= 0) return '';
    $names = [
        1 => 'Strength', 2 => 'Dexterity', 3 => 'Constitution', 4 => 'Quickness',
        5 => 'Intelligence', 6 => 'Piety', 7 => 'Empathy', 8 => 'Charisma',
        10 => 'Hit Points', 22 => 'All Melee Skills', 202 => 'Resist Slash',
        203 => 'Resist Thrust', 204 => 'Resist Crush'
        // Erweitere diese Liste nach Bedarf für deine DOL-DB
    ];
    $name = $names[$type] ?? "Stat #$type";
    return "<div class='stat-row'><span>$name:</span> <span class='stat-val'>+$value</span></div>";
}
?>

<style>
    .item-detail-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
    }
    .item-card-visual {
        background: rgba(10, 10, 10, 0.9);
        border: 1px solid var(--gold);
        padding: 25px;
        border-radius: 4px;
        box-shadow: 0 0 20px rgba(197, 160, 89, 0.1);
        position: relative;
    }
    .item-card-visual::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, var(--gold), transparent);
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        font-size: 12px;
    }
    .stat-val { color: #eee; font-family: monospace; }
    .restriction-box {
        margin-top: 20px;
        padding: 15px;
        font-size: 11px;
        border-radius: 3px;
    }
    .can-use { background: rgba(0, 255, 0, 0.05); border: 1px solid rgba(0, 255, 0, 0.2); color: #8f8; }
    .cannot-use { background: rgba(255, 0, 0, 0.05); border: 1px solid rgba(255, 0, 0, 0.2); color: #f88; }
</style>

<div class="admin-container">
    <div style="margin-bottom: 25px;">
        <a href="javascript:history.back()" style="color:#555; text-decoration:none; font-size:10px;">&larr; BACK TO CHRONICLES</a>
    </div>

    <div class="item-detail-container">
        <div class="item-card-visual">
            <h3 style="font-family:'Cinzel'; color:var(--gold); margin-top:0;"><?php echo htmlspecialchars($item['Name']); ?></h3>
            <div style="color:#666; font-size:10px; text-transform:uppercase; margin-bottom:15px;">
                <?php echo str_replace('_', ' ', $item['Object_Type']); ?>
            </div>

            <div class="stat-row"><span>Level:</span> <span class="stat-val"><?php echo $item['Level']; ?></span></div>
            <div class="stat-row"><span>Quality:</span> <span class="stat-val"><?php echo $item['Quality']; ?>%</span></div>
            
            <?php if($item['DPS'] > 0): ?>
                <div class="stat-row"><span>DPS:</span> <span class="stat-val"><?php echo number_format($item['DPS']/10, 1); ?></span></div>
                <div class="stat-row"><span>Speed:</span> <span class="stat-val"><?php echo number_format($item['Speed']/10, 1); ?>s</span></div>
            <?php endif; ?>

            <div style="margin-top:15px; border-top: 1px solid #333; padding-top:10px;">
                <?php 
                for($i=1; $i<=10; $i++) {
                    echo renderBonus($item["BonusType$i"], $item["Bonus$i"]);
                }
                ?>
            </div>

            <?php 
            // Hier prüfen wir auf deine Atlantis-Klassen Logik
            $can_wear = true;
            $reason = "You can wield this artifact.";
            
            // Beispiel: Barbarians (Myrmidons) nutzen keine Hämmer
            if ($item['Object_Type'] == 'Hammer' || $item['Object_Type'] == 'Axe') {
                $can_wear = false;
                $reason = "Atlantis classes (Barbarian/Poet) cannot master this weapon type.";
            }
            ?>
            <div class="restriction-box <?php echo $can_wear ? 'can-use' : 'cannot-use'; ?>">
                <i class="fas <?php echo $can_wear ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>" style="margin-right:8px;"></i>
                <?php echo $reason; ?>
            </div>
        </div>

        <div>
            <h4 style="font-family:'Cinzel'; color:#444;">Known Sources</h4>
            <div style="background:rgba(0,0,0,0.2); padding:15px; border:1px solid #111;">
                <?php 
                // Suche alle Mobs, die dieses Item im Loot-Table haben
                $sources = $db->query("
                    SELECT m.Name, m.Level, m.Region 
                    FROM mob m
                    JOIN loottemplate lt ON m.DropsId = lt.LootTemplate_ID
                    JOIN lootitem li ON lt.LootTemplate_ID = li.LootTemplate_ID
                    WHERE li.ItemTemplate_ID = '$item_id'
                    LIMIT 5");
                
                if($sources->num_rows > 0): 
                    while($s = $sources->fetch_assoc()): ?>
                        <div style="font-size:12px; margin-bottom:10px; padding-bottom:5px; border-bottom:1px solid #111;">
                            <strong style="color:#888;"><?php echo htmlspecialchars($s['Name']); ?></strong> (Lvl <?php echo $s['Level']; ?>)<br>
                            <span style="font-size:10px; color:#444;">Region: <?php echo $s['Region']; ?></span>
                        </div>
                    <?php endwhile; 
                else: ?>
                    <p style="font-size:11px; color:#444;">No documented drops for this item yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>