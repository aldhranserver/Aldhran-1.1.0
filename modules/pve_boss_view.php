<?php
if (!isset($db)) require_once('includes/db.php');

$mob_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$mob_id) {
    echo "<div class='admin-container'><p>Entity not found.</p></div>";
    return;
}

// Boss-Daten laden
$mob = $db->query("SELECT * FROM mob WHERE Mob_ID = $mob_id")->fetch_assoc();

if (!$mob) return;

// Prüfen, ob der Mob ein Boss ist (z.B. Level 50+ oder Named)
$is_epic = ($mob['Level'] >= 50);
?>

<style>
    .boss-header {
        position: relative;
        background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.9)), url('img/bg/boss_pattern.jpg');
        border: 1px solid rgba(197,160,89,0.2);
        padding: 40px;
        text-align: center;
        border-radius: 4px;
        margin-bottom: 30px;
        overflow: hidden;
    }
    .boss-header::after {
        content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, var(--gold), transparent);
    }
    .boss-title { font-family: 'Cinzel'; font-size: 2.5em; color: var(--gold); text-shadow: 0 0 20px rgba(197,160,89,0.3); margin: 10px 0; }
    
    .encounter-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
    
    .mechanic-box { background: rgba(255,255,255,0.02); border-left: 3px solid var(--gold); padding: 20px; margin-bottom: 20px; }
    .mechanic-title { font-family: 'Cinzel'; color: var(--gold); font-size: 0.9em; margin-bottom: 10px; text-transform: uppercase; }
    
    .loot-sidebar { background: rgba(10,10,10,0.5); border: 1px solid #111; padding: 20px; }
</style>

<div class="admin-container">
    <div style="margin-bottom: 20px;">
        <a href="?p=pve_bestiary" style="color:#555; text-decoration:none; font-size:10px;">&larr; BACK TO BESTIARY</a>
    </div>

    <div class="boss-header">
        <div style="text-transform: uppercase; letter-spacing: 4px; font-size: 10px; color: #555;">Elite Encounter</div>
        <h1 class="boss-title"><?php echo htmlspecialchars($mob['Name']); ?></h1>
        <div style="color: var(--gold); opacity: 0.6; font-family: 'Cinzel';">Level <?php echo $mob['Level']; ?> // <?php echo $mob['Region']; ?></div>
    </div>

    <div class="encounter-grid">
        <div>
            <h3 style="font-family:'Cinzel'; color:#444; border-bottom: 1px solid #111; padding-bottom: 10px;">The Encounter</h3>
            
            <div class="mechanic-box">
                <div class="mechanic-title"><i class="fas fa-skull-crossbones"></i> Guardian Traits</div>
                <p style="font-size: 13px; color: #888; line-height: 1.6;">
                    This entity is known to be <?php echo $mob['AggroRange'] > 0 ? 'highly aggressive' : 'passive until provoked'; ?>. 
                    It commands a power level of <?php echo $mob['Level']; ?> and guards the region of <?php echo $mob['Region']; ?>.
                </p>
            </div>

            <div class="mechanic-box" style="border-left-color: #600;">
                <div class="mechanic-title" style="color: #600;">Tactical Note</div>
                <p style="font-size: 13px; color: #888; line-height: 1.6;">
                    Beware of the high health pool and magical resistances. Bring a balanced party of Atlantis classes, especially a <strong>Poet</strong> for inspiration and a <strong>Myrmidon</strong> for frontline control.
                </p>
            </div>
        </div>

        <div class="loot-sidebar">
            <h3 style="font-family:'Cinzel'; color:var(--gold); font-size: 1em; margin-bottom: 20px;">Relics of Power</h3>
            <?php 
            if (!empty($mob['DropsId'])): 
                $tid = mysqli_real_escape_string($db, $mob['DropsId']);
                $items = $db->query("
                    SELECT it.Name, it.Id_nb, it.Quality, li.Chance 
                    FROM lootitem li 
                    JOIN itemtemplate it ON li.ItemTemplate_ID = it.Id_nb 
                    WHERE li.LootTemplate_ID = '$tid' 
                    ORDER BY li.Chance ASC LIMIT 8");
                
                while($i = $items->fetch_assoc()): ?>
                    <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 12px;">
                            <a href="?p=pve_item&id=<?php echo $i['Id_nb']; ?>" style="color: #ccc; text-decoration: none;"><?php echo htmlspecialchars($i['Name']); ?></a>
                            <div style="font-size: 9px; color: #444;"><?php echo $i['Quality']; ?>% Quality</div>
                        </div>
                        <div style="font-size: 10px; color: var(--gold);"><?php echo $i['Chance']; ?>%</div>
                    </div>
                <?php endwhile; ?>
                <a href="?p=pve_loot&id=<?php echo urlencode($mob['DropsId']); ?>" style="display:block; text-align:center; font-size:10px; color:#555; text-decoration:none; margin-top:10px;">VIEW FULL TABLE</a>
            <?php else: ?>
                <p style="font-size: 11px; color: #444;">No documented relics.</p>
            <?php endif; ?>
        </div>
    </div>
</div>