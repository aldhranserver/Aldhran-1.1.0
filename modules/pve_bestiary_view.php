<?php
/**
 * PVE BESTIARY VIEW - Aldhran V1.6.4 - Icon & Path Fix
 * Fix: Added base path variable for icons and verified TemplateName Join.
 */
require_once('includes/db.php');

// --- AJAX HANDLER ---
if (isset($_POST['get_spawns'])) {
    if (ob_get_length()) ob_clean(); 
    
    $name = mysqli_real_escape_string($conn, $_POST['mob_name']);
    $region = mysqli_real_escape_string($conn, $_POST['region']);
    $level = (int)$_POST['level'];

    // 1. Koordinaten holen
    $res_spawns = $conn->query("SELECT X, Y, Z FROM mob WHERE Name = '$name' AND Region = '$region' AND Level = $level LIMIT 50");
    
    // 2. Loot holen
    $loot_sql = "
        SELECT lt.ItemTemplateID, it.Name as ItemName, lt.Chance, it.Model
        FROM mob m
        JOIN mobxloottemplate mxl ON m.Name = mxl.MobName
        JOIN loottemplate lt ON mxl.LootTemplateName = lt.TemplateName
        LEFT JOIN itemtemplate it ON lt.ItemTemplateID = it.Id_NB
        WHERE m.Name = '$name' AND m.Region = '$region' AND m.Level = $level
        GROUP BY lt.ItemTemplateID
    ";
    $res_loot = $conn->query($loot_sql);

    echo '<div style="display:flex; gap:25px; flex-wrap:wrap;">';
        
        // Spalte: Locations
        echo '<div style="flex:1; min-width:220px;">';
            echo '<h4 style="color:var(--gold); font-size:11px; border-bottom:1px solid #222; padding-bottom:8px; letter-spacing:1px;">LOCATIONS</h4>';
            echo '<table class="spawn-table"><thead><tr><th>X</th><th>Y</th><th>Z</th></tr></thead><tbody>';
            if ($res_spawns && $res_spawns->num_rows > 0) {
                while ($s = $res_spawns->fetch_assoc()) {
                    echo "<tr><td>".round($s['X'])."</td><td>".round($s['Y'])."</td><td>".round($s['Z'])."</td></tr>";
                }
            } else { echo "<tr><td colspan='3'>No locations found.</td></tr>"; }
            echo '</tbody></table>';
        echo '</div>';

        // Spalte: Loot
        echo '<div style="flex:1.5; min-width:280px;">';
            echo '<h4 style="color:var(--gold); font-size:11px; border-bottom:1px solid #222; padding-bottom:8px; letter-spacing:1px;">POTENTIAL DROPS</h4>';
            echo '<table class="spawn-table"><thead><tr><th colspan="2">Item</th><th>Chance</th></tr></thead><tbody>';
            if ($res_loot && $res_loot->num_rows > 0) {
                while ($l = $res_loot->fetch_assoc()) {
                    $itemName = $l['ItemName'] ?: $l['ItemTemplateID'];
                    
                    // KONFIGURATION: Hier den Pfad zu deinen Icons anpassen!
                    $iconBase = "assets/img/icons/items/"; 
                    $iconFile = (!empty($l['Model'])) ? $l['Model'] . ".png" : "default.png";
                    $fullPath = $iconBase . $iconFile;
                    
                    echo "<tr>";
                    echo "<td style='width:32px; text-align:center;'>";
                    echo "<img src='$fullPath' title='Model: ".$l['Model']."' onerror=\"this.src='{$iconBase}default.png'; this.title='Missing Icon: ".$l['Model']."';\" style='width:24px; height:24px; border:1px solid #333; background:#000; padding:1px; display:inline-block;'>";
                    echo "</td>";
                    echo "<td><span style='color:#eee;'>".$itemName."</span></td>";
                    echo "<td style='color:var(--gold); text-align:right;'>".$l['Chance']."%</td>";
                    echo "</tr>";
                }
            } else { echo "<tr><td colspan='3' style='color:#444; padding:20px 0;'>No loot assigned.</td></tr>"; }
            echo '</tbody></table>';
        echo '</div>';

    echo '</div>';
    exit(); 
}

// 1. Parameter-Erfassung
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$realm = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'lvl_desc';
$min_lvl = (int)($_GET['min_lvl'] ?? 1);
$max_lvl = (int)($_GET['max_lvl'] ?? 60);

// 2. Sortierung
switch($sort) {
    case 'name_asc':  $order_by = "Name ASC"; break;
    case 'lvl_asc':   $order_by = "Level ASC, Name ASC"; break;
    case 'region':    $order_by = "Region ASC, Level DESC"; break;
    default:          $order_by = "Level DESC, Name ASC"; break; 
}

// 3. WHERE & Pagination
$where = "WHERE Name LIKE '%$search%' AND Level BETWEEN $min_lvl AND $max_lvl";
if ($realm > 0) $where .= " AND Realm = $realm";

$limit = 30; 
$page = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) FROM (SELECT Name FROM mob $where GROUP BY Name, Region, Level) AS grouped";
$total_res = $conn->query($count_query);
$total_mobs = ($total_res) ? $total_res->fetch_row()[0] : 0;
$total_pages = ceil($total_mobs / $limit);

$mobs = $conn->query("SELECT Name, Level, Realm, Region, COUNT(*) as spawn_count 
                      FROM mob $where 
                      GROUP BY Name, Region, Level 
                      ORDER BY $order_by LIMIT $limit OFFSET $offset");

if (!function_exists('getRealmName')) {
    function getRealmName($id) {
        switch($id) { case 1: return "Albion"; case 2: return "Midgard"; case 3: return "Hibernia"; default: return "Neutral"; }
    }
}
?>

<style>
    .bestiary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px; }
    .mob-card { 
        background: rgba(15, 15, 15, 0.9); border: 1px solid rgba(197, 160, 89, 0.1); 
        padding: 15px; border-radius: 4px; border-left: 3px solid #333; cursor: pointer; transition: 0.2s;
        position: relative;
    }
    .mob-card:hover { border-color: var(--gold); transform: translateY(-2px); background: #1a1a1a; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
    .mob-card.is-boss { border-left-color: var(--gold); }
    .spawn-badge { font-size: 10px; color: var(--gold); background: rgba(197, 160, 89, 0.1); padding: 2px 6px; border-radius: 10px; margin-left: 8px; border: 1px solid rgba(197, 160, 89, 0.2); }

    #spawnModal {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.9); backdrop-filter: blur(8px); align-items: center; justify-content: center;
    }
    .modal-content {
        background: #0d0d0d; border: 1px solid var(--gold); padding: 30px; width: 95%; max-width: 850px;
        border-radius: 4px; box-shadow: 0 0 50px rgba(0,0,0,1); position: relative; color: #eee;
    }
    .spawn-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
    .spawn-table th { color: #444; text-align: left; padding: 5px; border-bottom: 1px solid #222; text-transform: uppercase; font-size: 9px; }
    .spawn-table td { padding: 8px 5px; border-bottom: 1px solid #161616; color: #aaa; vertical-align: middle; }
    
    .pg-link { padding: 8px 12px; background: rgba(0,0,0,0.3); color: #888; text-decoration: none; border: 1px solid #222; font-size: 11px; transition: 0.2s; }
    .pg-active { background: var(--gold) !important; color: #000 !important; border-color: var(--gold) !important; }
    .pg-link:hover { border-color: var(--gold); color:#eee; }
</style>

<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h2 style="font-family:'Cinzel'; color:var(--gold); margin:0;">Bestiary</h2>
        <span style="font-size: 11px; color: #444; letter-spacing: 1px;"><?php echo $total_mobs; ?> ENTITIES</span>
    </div>

    <form method="GET" class="filter-bar" style="background: rgba(0,0,0,0.2); padding: 15px; border: 1px solid #111; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="p" value="<?php echo htmlspecialchars($_GET['p'] ?? ''); ?>">
        <div style="flex:1; min-width: 150px;"><label class="um-label">Name</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="um-input"></div>
        <div style="width:130px;"><label class="um-label">Realm</label>
            <select name="realm" class="um-input">
                <option value="0">All</option><option value="1" <?php if($realm==1) echo 'selected'; ?>>Albion</option><option value="2" <?php if($realm==2) echo 'selected'; ?>>Midgard</option><option value="3" <?php if($realm==3) echo 'selected'; ?>>Hibernia</option>
            </select>
        </div>
        <div style="width:140px;"><label class="um-label">Sort</label>
            <select name="sort" class="um-input">
                <option value="lvl_desc" <?php if($sort=='lvl_desc') echo 'selected'; ?>>Lvl High</option><option value="lvl_asc" <?php if($sort=='lvl_asc') echo 'selected'; ?>>Lvl Low</option><option value="name_asc" <?php if($sort=='name_asc') echo 'selected'; ?>>Name A-Z</option><option value="region" <?php if($sort=='region') echo 'selected'; ?>>Region</option>
            </select>
        </div>
        <button type="submit" class="btn-gold" style="height:34px; padding: 0 25px;">FILTER</button>
    </form>

    <div class="bestiary-grid">
        <?php if ($mobs && $mobs->num_rows > 0): ?>
            <?php while($m = $mobs->fetch_assoc()): ?>
                <div class="mob-card <?php echo ($m['Level'] >= 50) ? 'is-boss' : ''; ?>" 
                     onclick="showDetails('<?php echo addslashes($m['Name']); ?>', '<?php echo addslashes($m['Region']); ?>', <?php echo $m['Level']; ?>)">
                    <div style="font-size: 9px; color: #555;"><?php echo getRealmName($m['Realm']); ?></div>
                    <div style="font-family: 'Cinzel'; color: #eee; margin: 5px 0;"><?php echo htmlspecialchars($m['Name']); ?><?php if($m['spawn_count'] > 1): ?><span class="spawn-badge">x<?php echo $m['spawn_count']; ?></span><?php endif; ?></div>
                    <div style="font-size: 11px; color: #666;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($m['Region']); ?></div>
                    <div style="position: absolute; bottom: 12px; right: 12px; color: var(--gold); font-family: 'Cinzel'; opacity: 0.8;">Lvl <?php echo $m['Level']; ?></div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div style="margin-top:40px; display:flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
            <?php 
                $range = 2; $p_val = htmlspecialchars($_GET['p'] ?? '');
                $base_url = "?p=$p_val&search=".urlencode($search)."&realm=".$realm."&sort=".$sort."&min_lvl=$min_lvl&max_lvl=$max_lvl";
                for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++): ?>
                    <a href="<?php echo $base_url; ?>&pg=<?php echo $i; ?>" class="pg-link <?php echo ($i == $page) ? 'pg-active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; 
            ?>
        </div>
    <?php endif; ?>
</div>

<div id="spawnModal">
    <div class="modal-content">
        <button onclick="document.getElementById('spawnModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; color:var(--gold); font-size:26px; cursor:pointer;">&times;</button>
        <h3 id="modalTitle" style="font-family:'Cinzel'; color:var(--gold); margin:0;"></h3>
        <p id="modalSub" style="font-size: 10px; color: #555; margin-bottom: 25px; text-transform: uppercase;"></p>
        <div id="modalBody" style="max-height: 500px; overflow-y: auto;"></div>
    </div>
</div>

<script>
function showDetails(name, region, level) {
    const modal = document.getElementById('spawnModal');
    const body = document.getElementById('modalBody');
    document.getElementById('modalTitle').innerText = name;
    document.getElementById('modalSub').innerText = region + " • LEVEL " + level;
    body.innerHTML = "<div style='text-align:center; padding:60px;'><span style='font-size:10px; color:#444;'>Loading...</span></div>";
    modal.style.display = 'flex';
    let fd = new FormData();
    fd.append('get_spawns', 1);
    fd.append('mob_name', name);
    fd.append('region', region);
    fd.append('level', level);
    fetch(window.location.href, { method: 'POST', body: fd }).then(r => r.text()).then(t => { body.innerHTML = t; });
}
window.onclick = function(e) { 
    if (e.target == document.getElementById('spawnModal')) {
        document.getElementById('spawnModal').style.display = "none";
    }
}
</script>