<?php
/**
 * PUBLIC USER VIEW - Aldhran Freeshard
 * Version: 0.8.2 - Variable Fix ($db -> $conn)
 */

// Sicherheitscheck für die Datenbankverbindung
if (!isset($conn)) {
    require_once('includes/db.php');
}

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// FIX: Variable von $db auf $conn geändert
$res = $conn->query("SELECT username, languages, description, avatar_url FROM users WHERE id = $target_id");

if (!$res || $res->num_rows === 0) {
    echo "<div class='admin-box'>The user you seek does not exist. Maybe a typo?</div>";
    return;
}

$u = $res->fetch_assoc();
$u_name_esc = mysqli_real_escape_string($conn, $u['username']);

// --- REICHE ERMITTELN ---
$realm_res = $conn->query("SELECT DISTINCT Realm FROM dolcharacters WHERE AccountName = '$u_name_esc'");
$realms = [];
while ($r = $realm_res->fetch_assoc()) {
    if ($r['Realm'] == 1) $realms[] = "<span style='color:#F52727;'>Albion</span>";
    if ($r['Realm'] == 2) $realms[] = "<span style='color:#275BF5;'>Midgard</span>";
    if ($r['Realm'] == 3) $realms[] = "<span style='color:#27F565;'>Hibernia</span>";
}
$realm_list = !empty($realms) ? implode(", ", $realms) : "No realm chosen yet";
?>

<div class="admin-container">
    <div class="admin-box" style="padding: 40px; border-top: 2px solid var(--gold);">
        <div style="display: flex; gap: 40px; align-items: flex-start;">
            
            <div style="text-align: center; width: 150px;">
                <?php if(!empty($u['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($u['avatar_url']); ?>" style="width: 120px; height: 120px; border: 1px solid var(--gold); object-fit: cover;">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; border: 1px solid #222; display: flex; align-items: center; justify-content: center; background: #050505;">
                        <i class="fas fa-user-circle" style="font-size: 80px; color: #111;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div style="flex: 1;">
                <h1 style="margin: 0 0 10px 0; font-family: 'Cinzel'; color: var(--gold);"><?php echo htmlspecialchars($u['username']); ?></h1>
                
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 25px;">
                    
                    <div>
                        <label class="um-label" style="display:block; margin-bottom: 5px;">Active in Realms</label>
                        <div style="font-family: 'Cinzel'; font-size: 1.1em;"><?php echo $realm_list; ?></div>
                    </div>

                    <div>
                        <label class="um-label" style="display:block; margin-bottom: 5px;">Known Tongues</label>
                        <div style="color: #ccc;"><?php echo !empty($u['languages']) ? htmlspecialchars($u['languages']) : "Unknown"; ?></div>
                    </div>

                    <div style="border-top: 1px solid #111; padding-top: 20px;">
                        <label class="um-label" style="display:block; margin-bottom: 10px;">Biography</label>
                        <div style="color: #888; font-style: italic; line-height: 1.6; white-space: pre-wrap;"><?php echo !empty($u['description']) ? htmlspecialchars($u['description']) : "This soul has not yet written its legend."; ?></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>