<?php
/**
 * GUILD HERALD DETAILS - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Prepared Statements
 */

$guild_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($guild_id === 0) {
    echo "<div class='admin-box'>No guild specified.</div>";
    return;
}

// 1. GUILD DATA (PDO Prepared Statement)
$stmt_guild = $db->prepare("SELECT * FROM guild WHERE GuildID = ?");
$stmt_guild->execute([$guild_id]);
$g = $stmt_guild->fetch();

if (!$g) {
    echo "<div class='admin-box'>Guild not found in the chronicles.</div>";
    return;
}

// 2. MEMBER DATA (PDO Prepared Statement)
$stmt_members = $db->prepare("
    SELECT Name, Class, Level, RealmPoints, Rank 
    FROM dolcharacters 
    WHERE GuildID = ? 
    ORDER BY RealmPoints DESC
");
$stmt_members->execute([$guild_id]);
$members = $stmt_members->fetchAll(); // Holt alle Mitglieder in ein Array
$member_count = count($members);

// Realm Mapping (Bleibt gleich)
$realm_id = (int)($g['Realm'] ?? 0);
$r_info = [
    1 => ['name' => 'Albion', 'color' => '#4a90e2'],
    2 => ['name' => 'Midgard', 'color' => '#e74c3c'],
    3 => ['name' => 'Hibernia', 'color' => '#2ecc71']
];
$r_color = $r_info[$realm_id]['color'] ?? '#555';
?>

<div class="admin-container" style="--realm-color: <?php echo $r_color; ?>;">
    <div style="margin-bottom: 20px;">
        <a href="?p=herald" class="back-link">
            <i class="fas fa-chevron-left"></i> Back to Herald
        </a>
    </div>

    <div class="admin-box" style="border-top: 3px solid var(--realm-color); padding: 40px;">
        <div class="guild-header">
            <div class="guild-subtitle">Guild Chronicles</div>
            <h1 class="guild-name">
                &lt; <?php echo h($g['GuildName'] ?? $g['Name']); ?> &gt;
            </h1>
            <div class="realm-affiliation">
                Sworn to <?php echo $r_info[$realm_id]['name'] ?? 'Unknown'; ?>
            </div>
        </div>

        <div class="roster-meta">
            <h3 class="roster-title">
                <i class="fas fa-users"></i> Member Roster
            </h3>
            <span class="soul-count"><?php echo $member_count; ?> Souls registered</span>
        </div>
        
        <table class="guild-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Level</th>
                    <th style="text-align: right; padding-right: 10px;">Realm Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($member_count > 0): ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td>
                            <a href="?p=herald_char&name=<?php echo urlencode($m['Name']); ?>" class="char-link">
                                <?php echo h($m['Name']); ?>
                            </a>
                        </td>
                        <td class="td-dim"><?php echo h($m['Class']); ?></td>
                        <td class="td-dim"><?php echo (int)$m['Level']; ?></td>
                        <td class="td-rp">
                            <?php echo number_format($m['RealmPoints']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding: 20px; text-align: center; color: #444;">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>