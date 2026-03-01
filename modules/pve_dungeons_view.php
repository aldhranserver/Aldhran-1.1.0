<?php
/**
 * PVE DUNGEONS VIEW - Aldhran V1.0 - Standalone
 */
// Verbindung sicherstellen
require_once('includes/db.php');

/**
 * Dungeon-Definitionen
 */
$dungeons = [
    ['name' => 'Darkness Falls', 'region' => 'Darkness Falls', 'lvl' => '30-60', 'realm' => 'Neutral', 'img' => 'img/dungeons/df.jpg'],
    ['name' => 'City of Aerus', 'region' => 'Aerus', 'lvl' => '45-50', 'realm' => 'Atlantis', 'img' => 'img/dungeons/aerus.jpg'],
    ['name' => 'Spindelhalla', 'region' => 'Spindelhalla', 'lvl' => '40-50', 'realm' => 'Midgard', 'img' => 'img/dungeons/spindel.jpg'],
    ['name' => 'Catacombs of Cardis', 'region' => 'Cardis', 'lvl' => '20-30', 'realm' => 'Albion', 'img' => 'img/dungeons/cardis.jpg'],
    ['name' => 'Tomb of Mithra', 'region' => 'Mithra', 'lvl' => '10-20', 'realm' => 'Albion', 'img' => 'img/dungeons/mithra.jpg'],
    ['name' => 'Sobekite Ravine', 'region' => 'Sobekite', 'lvl' => '45-50', 'realm' => 'Atlantis', 'img' => 'img/dungeons/sobek.jpg']
];
?>

<style>
    .dungeon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    .dungeon-card {
        background: linear-gradient(135deg, rgba(20,20,20,0.9) 0%, rgba(10,10,10,0.95) 100%);
        border: 1px solid rgba(197, 160, 89, 0.1);
        display: flex;
        min-height: 220px;
        border-radius: 4px;
        transition: 0.4s;
        position: relative;
    }
    .dungeon-card:hover { 
        border-color: var(--gold); 
        transform: translateY(-5px);
    }
    .dungeon-img {
        width: 140px;
        background-size: cover;
        background-position: center;
        filter: grayscale(100%) brightness(0.4);
        border-right: 1px solid rgba(197, 160, 89, 0.1);
    }
    .dungeon-card:hover .dungeon-img { filter: grayscale(30%) brightness(0.7); }
    .dungeon-info { padding: 20px; flex: 1; display: flex; flex-direction: column; }
    .quest-tag-list { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; }
    .quest-tag {
        font-size: 9px;
        background: rgba(197, 160, 89, 0.05);
        color: #888;
        padding: 3px 10px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        text-decoration: none;
    }
    .quest-tag:hover { border-color: var(--gold); color: var(--gold); }
    .btn-save-mini {
        padding: 6px 12px;
        background: var(--gold);
        color: #000;
        font-weight: bold;
        border-radius: 2px;
    }
</style>

<div class="admin-container">
    <div style="border-bottom: 1px solid #111; padding-bottom: 20px; margin-bottom: 10px;">
        <h2 style="font-family:'Cinzel'; color:var(--gold); letter-spacing:4px; margin:0; text-transform: uppercase;">Dungeon Hub</h2>
        <p style="color:#555; font-size: 0.75em; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px;">
            Strategic Mapping of Regional Points of Interest
        </p>
    </div>

    <div class="dungeon-grid">
        <?php foreach($dungeons as $d): 
            // Korrektur: $conn statt $db verwenden
            $r_esc = mysqli_real_escape_string($conn, $d['region']);
            
            // 1. Mobs zählen
            $mob_res = $conn->query("SELECT COUNT(*) FROM mob WHERE Region = '$r_esc'");
            $mob_count = ($mob_res) ? $mob_res->fetch_row()[0] : 0;
            
            // 2. Quest-Abfrage
            $check_column = $conn->query("SHOW COLUMNS FROM `quest` LIKE 'StartRegion'");
            if ($check_column && $check_column->num_rows > 0) {
                $quest_res = $conn->query("SELECT * FROM quest WHERE StartRegion = '$r_esc' LIMIT 3");
            } else {
                $quest_res = $conn->query("SELECT * FROM quest WHERE Name LIKE '%$r_esc%' LIMIT 3");
            }
        ?>
            <div class="dungeon-card">
                <div class="dungeon-img" style="background-image: url('<?php echo $d['img']; ?>');"></div>
                
                <div class="dungeon-info">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-size: 9px; color:#666; letter-spacing:1px; text-transform: uppercase;">
                                <?php echo $d['realm']; ?> // LVL <?php echo $d['lvl']; ?>
                            </div>
                            <h3 style="font-family:'Cinzel'; color:#ccc; margin: 8px 0; letter-spacing: 1px;">
                                <?php echo $d['name']; ?>
                            </h3>
                        </div>
                        <div title="Manifested Entities" style="font-size: 10px; color: #444; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-skull"></i> <?php echo $mob_count; ?>
                        </div>
                    </div>

                    <div style="margin-top: 15px; flex-grow: 1;">
                        <span style="font-size: 9px; text-transform:uppercase; color:#333; letter-spacing: 1px;">Available Missions:</span>
                        <div class="quest-tag-list">
                            <?php if($quest_res && $quest_res->num_rows > 0): ?>
                                <?php while($q = $quest_res->fetch_assoc()): 
                                    $q_id = $q['Quest_ID'] ?? $q['ID'] ?? $q['id'] ?? '';
                                ?>
                                    <a href="?p=pve_quest_detail&id=<?php echo urlencode((string)$q_id); ?>" class="quest-tag">
                                        <i class="fas fa-scroll" style="font-size: 0.8em; opacity: 0.5;"></i> 
                                        <?php echo htmlspecialchars($q['Name'] ?? 'Unnamed Quest'); ?>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <span style="font-size: 9px; color:#222; font-style:italic; padding: 3px 0;">No missions found.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top:20px; display:flex; gap:10px;">
                        <a href="?p=pve_bestiary&region=<?php echo urlencode($d['region'] ?? ''); ?>" class="btn-save-mini" style="font-size:9px; text-decoration:none;">
                            OPEN BESTIARY
                        </a>
                        <a href="?p=pve_quests&search=<?php echo urlencode($d['region'] ?? ''); ?>" class="btn-save-mini" style="font-size:9px; text-decoration:none; background:transparent; border:1px solid #222; color: #555;">
                            REGION LOGS
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>