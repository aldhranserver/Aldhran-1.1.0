<?php
/**
 * GUILD HERALD DETAILS
 * Version: 0.9.0 - Clean CSS Migration
 */

$guild_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($guild_id === 0) {
    echo "<div class='admin-box'>No guild specified.</div>";
    return;
}

$g_res = $db->query("SELECT * FROM guild WHERE GuildID = $guild_id");

if (!$g_res || $g_res->num_rows === 0) {
    echo "<div class='admin-box'>Guild not found in the chronicles.</div>";
    return;
}

$g = $g_res->fetch_assoc();

$m_res = $db->query("
    SELECT Name, Class, Level, RealmPoints, Rank 
    FROM dolcharacters 
    WHERE GuildID = $guild_id 
    ORDER BY RealmPoints DESC
");

// Realm Mapping
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
                &lt; <?php echo htmlspecialchars($g['GuildName'] ?? $g['Name']); ?> &gt;
            </h1>
            <div class="realm-affiliation">
                Sworn to <?php echo $r_info[$realm_id]['name'] ?? 'Unknown'; ?>
            </div>
        </div>

        <div class="roster-meta">
            <h3 class="roster-title">
                <i class="fas fa-users"></i> Member Roster
            </h3>
            <span class="soul-count"><?php echo $m_res->num_rows; ?> Souls registered</span>
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
                <?php if ($m_res && $m_res->num_rows > 0): ?>
                    <?php while ($m = $m_res->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="?p=herald_char&name=<?php echo urlencode($m['Name']); ?>" class="char-link">
                                <?php echo htmlspecialchars($m['Name']); ?>
                            </a>
                        </td>
                        <td class="td-dim"><?php echo htmlspecialchars($m['Class']); ?></td>
                        <td class="td-dim"><?php echo (int)$m['Level']; ?></td>
                        <td class="td-rp">
                            <?php echo number_format($m['RealmPoints']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding: 20px; text-align: center; color: #444;">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>