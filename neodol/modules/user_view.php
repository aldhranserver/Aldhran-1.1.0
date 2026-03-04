<?php
/**
 * PUBLIC USER VIEW - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Optimized Realm Detection
 */

if (!defined('IN_CMS')) { exit; }

// Wir nutzen das globale PDO-Objekt $db aus der db.php
global $db;

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. User-Daten via PDO laden
$stmt_u = $db->prepare("SELECT username, languages, description, avatar_url FROM users WHERE id = ? LIMIT 1");
$stmt_u->execute([$target_id]);
$u = $stmt_u->fetch();

if (!$u) {
    echo "<div class='admin-box' style='color:var(--glow-gold); text-align:center;'>The soul you seek has departed or never existed in these chronicles.</div>";
    return;
}

// 2. Reiche ermitteln (DOL Integration)
$stmt_realm = $db->prepare("SELECT DISTINCT Realm FROM dolcharacters WHERE AccountName = ?");
$stmt_realm->execute([$u['username']]);
$realm_data = $stmt_realm->fetchAll(PDO::FETCH_COLUMN);

$realm_map = [
    1 => "<span style='color:#F52727;'>Albion</span>",
    2 => "<span style='color:#275BF5;'>Midgard</span>",
    3 => "<span style='color:#27F565;'>Hibernia</span>"
];

$realms = [];
foreach ($realm_data as $r_id) {
    if (isset($realm_map[$r_id])) {
        $realms[] = $realm_map[$r_id];
    }
}
$realm_list = !empty($realms) ? implode(", ", $realms) : "No realm chosen yet";
?>

<div class="admin-container">
    <div class="admin-box" style="padding: 40px; border-top: 2px solid var(--gold); background: rgba(0,0,0,0.4);">
        <div style="display: flex; gap: 40px; align-items: flex-start;">
            
            <div style="text-align: center; width: 150px;">
                <?php if(!empty($u['avatar_url'])): ?>
                    <img src="<?php echo h($u['avatar_url']); ?>" style="width: 120px; height: 120px; border: 1px solid var(--gold); object-fit: cover; box-shadow: 0 0 15px rgba(212,175,55,0.1);">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; border: 1px solid #222; display: flex; align-items: center; justify-content: center; background: #050505;">
                        <i class="fas fa-user-circle" style="font-size: 80px; color: #111;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div style="flex: 1;">
                <h1 style="margin: 0 0 10px 0; font-family: 'Cinzel'; color: var(--gold); letter-spacing: 2px;"><?php echo h($u['username']); ?></h1>
                
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 25px;">
                    
                    <div>
                        <label class="um-label" style="display:block; margin-bottom: 5px; opacity: 0.5;">Active in Realms</label>
                        <div style="font-family: 'Cinzel'; font-size: 1.1em;"><?php echo $realm_list; ?></div>
                    </div>

                    <div>
                        <label class="um-label" style="display:block; margin-bottom: 5px; opacity: 0.5;">Known Tongues</label>
                        <div style="color: #ccc;"><?php echo !empty($u['languages']) ? h($u['languages']) : "Common Tongue"; ?></div>
                    </div>

                    <div style="border-top: 1px solid #111; padding-top: 20px;">
                        <label class="um-label" style="display:block; margin-bottom: 10px; opacity: 0.5;">Biography</label>
                        <div style="color: #888; font-style: italic; line-height: 1.6; white-space: pre-wrap;"><?php echo !empty($u['description']) ? h($u['description']) : "This soul has not yet written its legend."; ?></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>