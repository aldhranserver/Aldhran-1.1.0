<?php
/**
 * portal_admin_view.php - DAoC Portal NR Moderation
 * Version: 1.0.3 - CLEANED SYNTAX
 */
if (!defined('IN_CMS')) { exit; }

// Only Super Admin access
if ($_SESSION['user_id'] != 2) {
    header("Location: index.php?p=home");
    exit;
}

if (isset($_GET['action'])) {
    $sid = (int)$_GET['id'];
    
    if ($_GET['action'] === 'approve') {
        $db->prepare("UPDATE daoc_servers SET is_active = 1 WHERE id = ?")->execute([$sid]);
        aldhran_log('PORTAL_MOD', "Super Admin approved Shard ID $sid", $_SESSION['user_id']);
    } 
    elseif ($_GET['action'] === 'suspend') {
        $db->prepare("UPDATE daoc_servers SET is_active = 0 WHERE id = ?")->execute([$sid]);
        aldhran_log('PORTAL_MOD', "Super Admin suspended Shard ID $sid", $_SESSION['user_id']);
    } 
    elseif ($_GET['action'] === 'delete') {
        $db->prepare("DELETE FROM daoc_servers WHERE id = ?")->execute([$sid]);
        aldhran_log('PORTAL_MOD', "Super Admin deleted Shard ID $sid", $_SESSION['user_id']);
    }
    header("Location: index.php?p=portal_admin");
    exit;
}

$shards = $db->query("SELECT * FROM daoc_servers ORDER BY is_active ASC, id DESC")->fetchAll();
?>

<div class="um-nexus-wrapper">
    <div class="um-internal-header">
        <h2 class="um-internal-title"><i class="fas fa-user-shield"></i> Portal NR - Super Admin Console</h2>
    </div>

    <div class="admin-box" style="padding:0; background:rgba(0,0,0,0.4); border: 1px solid #222;">
        <table style="width:100%; border-collapse:collapse; font-size:12px; color:#ccc;">
            <tr style="background:#111; text-align:left; color:#c5a059; font-family:'Cinzel';">
                <th style="padding:15px;">Shard Name</th>
                <th>Address</th>
                <th>API Key</th>
                <th>Status</th>
                <th style="text-align:right; padding-right:15px;">Actions</th>
            </tr>
            <?php foreach($shards as $s): ?>
            <tr style="border-bottom:1px solid #111; transition: 0.3s;" onmouseover="this.style.background='rgba(197,160,89,0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding:15px;">
                    <strong style="color:#fff;"><?php echo htmlspecialchars($s['server_name']); ?></strong>
                </td>
                <td style="font-family:monospace; color:#888;"><?php echo htmlspecialchars($s['server_ip']).":".$s['server_port']; ?></td>
                <td style="font-size:10px; color:#444;"><?php echo htmlspecialchars($s['api_key']); ?></td>
                <td>
                    <?php if($s['is_active']): ?>
                        <span style="color:#00ff00; font-size:10px;"><i class="fas fa-check-circle"></i> ACTIVE</span>
                    <?php else: ?>
                        <span style="color:#ffaa00; font-size:10px;"><i class="fas fa-hourglass-half"></i> PENDING</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right; padding-right:15px;">
                    <?php if(!$s['is_active']): ?>
                        <a href="?p=portal_admin&action=approve&id=<?php echo $s['id']; ?>" style="background:#004400; border:1px solid #00ff00; color:#00ff00; padding: 5px 10px; text-decoration: none; font-size: 10px; border-radius: 2px;">APPROVE</a>
                    <?php else: ?>
                        <a href="?p=portal_admin&action=suspend&id=<?php echo $s['id']; ?>" style="background:#442200; border:1px solid #ffaa00; color:#ffaa00; padding: 5px 10px; text-decoration: none; font-size: 10px; border-radius: 2px;">SUSPEND</a>
                    <?php endif; ?>
                    <a href="?p=portal_admin&action=delete&id=<?php echo $s['id']; ?>" onclick="return confirm('Eradicate Shard from Portal?')" style="color:#ff4444; margin-left:15px;"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>