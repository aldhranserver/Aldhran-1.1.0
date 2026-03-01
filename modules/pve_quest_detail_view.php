<?php
/**
 * PVE QUEST DETAIL VIEW - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Dynamic Schema Handling
 */
require_once('includes/db.php');

// Quest ID sicher erfassen
$quest_id = $_GET['id'] ?? '';

if (empty($quest_id)) {
    echo "<div class='admin-container'><p>No Quest ID provided.</p></div>"; 
    return;
}

// 1. Spalten-Mapping via PDO (Struktur-Check)
$stmt_cols = $db->query("SHOW COLUMNS FROM `quest`") or die("Archive Error.");
$columns = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

// Ermittlung der korrekten ID-Spalte (Quest_ID, ID oder id)
$id_col = in_array('Quest_ID', $columns) ? 'Quest_ID' : (in_array('ID', $columns) ? 'ID' : 'id');

// 2. Abfrage über das globale $db Objekt
$stmt_quest = $db->prepare("SELECT * FROM quest WHERE `$id_col` = ? LIMIT 1");
$stmt_quest->execute([$quest_id]);
$q = $stmt_quest->fetch();

if (!$q) {
    echo "<div class='admin-container'><p>This chronicle has been lost to time.</p></div>"; 
    return;
}

// 3. Bereinigung technischer Namen (Namespace Filter)
$rawName = $q['Name'] ?? 'Unknown';
if (strpos($rawName, '.') !== false) {
    $parts = explode('.', $rawName);
    $cleanName = preg_replace('/(?<!^)([A-Z])/', ' $1', end($parts));
} else {
    $cleanName = $rawName;
}

// 4. Dynamische Fallbacks für Inhalte
$desc_text = $q['Description'] ?? $q['Summary'] ?? 'The archives are silent on the details of this task.';
$reward_xp = $q['Experience'] ?? $q['XP'] ?? $q['RewardExperience'] ?? 0;
$reward_money = $q['Money'] ?? $q['Gold'] ?? $q['RewardMoney'] ?? 0;
$reward_item = $q['RewardItemTemplateID'] ?? $q['RewardItem'] ?? $q['Item_ID'] ?? null;
$min_lvl = $q['MinLevel'] ?? $q['LevelMin'] ?? $q['Level'] ?? '??';
?>

<style>
    .quest-detail-header { border-bottom: 1px solid #111; padding-bottom: 20px; margin-bottom: 30px; }
    .quest-meta { display: flex; gap: 20px; margin-top: 10px; font-size: 10px; color: #444; text-transform: uppercase; }
    .quest-meta strong { color: var(--gold); }
    .quest-section { background: rgba(15,15,15,0.5); border: 1px solid rgba(197,160,89,0.05); padding: 25px; margin-bottom: 20px; border-radius: 4px; }
    .quest-section h4 { font-family: 'Cinzel'; color: var(--gold); margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid rgba(197,160,89,0.1); padding-bottom: 10px; font-size: 0.9em; letter-spacing: 1px; }
    .reward-item { display: flex; justify-content: space-between; padding: 10px; background: rgba(0,0,0,0.2); margin-bottom: 5px; font-size: 11px; border-left: 2px solid var(--gold); }
</style>

<div class="admin-container">
    <div class="quest-detail-header">
        <a href="?p=pve_quests" style="color: #444; text-decoration: none; font-size: 10px; text-transform: uppercase;">&larr; Back to Chronicles</a>
        <h2 style="font-family:'Cinzel'; color:#eee; margin: 15px 0 5px 0; letter-spacing: 2px;"><?php echo h($cleanName); ?></h2>
        <div class="quest-meta">
            <span>Level: <strong><?php echo h($min_lvl); ?></strong></span>
            <span>Region: <strong><?php echo h($q['StartRegion'] ?? 'The Wilds'); ?></strong></span>
            <span>Archive ID: <strong><?php echo h($quest_id); ?></strong></span>
        </div>
    </div>

    <div class="quest-section">
        <h4>The Objective</h4>
        <p style="color: #888; line-height: 1.8; font-size: 13px; white-space: pre-line;"><?php echo h($desc_text); ?></p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="quest-section">
            <h4>Rewards</h4>
            <div class="reward-item"><span>Experience</span><span style="color: var(--gold);"><?php echo number_format((float)$reward_xp); ?></span></div>
            <div class="reward-item"><span>Currency</span><span style="color: var(--gold);"><?php echo number_format((float)$reward_money); ?></span></div>
            <?php if($reward_item): ?>
                <div class="reward-item">
                    <span>Special Artifact</span>
                    <a href="?p=pve_item&id=<?php echo urlencode((string)$reward_item); ?>" style="color: var(--gold); text-decoration:none;">VIEW ITEM</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="quest-section">
            <h4>Instructions</h4>
            <p style="font-size: 11px; color: #555; font-style: italic;">
                Seek out <?php echo h($q['StartNPCName'] ?? 'the Questgiver'); ?> to begin this journey.
            </p>
        </div>
    </div>
</div>