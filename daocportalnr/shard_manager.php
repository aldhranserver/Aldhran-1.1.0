<?php
/**
 * DAoC Portal NR - Shard Management Dashboard
 * Version: 1.3.1 - ZIP Hash Display
 */
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['portal_user_id'])) { header("Location: portal_login.php"); exit; }

$uid = (int)$_SESSION['portal_user_id'];
$username = $_SESSION['portal_username'];

if (isset($_GET['del'])) {
    $delId = (int)$_GET['del'];
    $stmtDel = $db->prepare("DELETE FROM daoc_servers WHERE id = ? AND owner_id = ?");
    if ($stmtDel->execute([$delId, $uid])) {
        header("Location: shard_manager.php?msg=deleted");
        exit;
    }
}

/**
 * Fetches Last-Modified of the ZIP via HTTP HEAD – no download required
 */
function getZipLastModified($zip_url) {
    if (empty($zip_url)) return null;
    $context = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 5]]);
    $headers = @get_headers($zip_url, 1, $context);
    if (!$headers) return null;
    $lm = $headers['Last-Modified'] ?? $headers['last-modified'] ?? null;
    return $lm ? date('d.m.Y H:i', strtotime($lm)) : null;
}

$stmtMyShards = $db->prepare("SELECT * FROM daoc_servers WHERE owner_id = ? ORDER BY id DESC");
$stmtMyShards->execute([$uid]);
$shards = $stmtMyShards->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shard Manager - DAoC Portal NR</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #ccc; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 40px; }
        .manager-window { max-width: 950px; margin: 0 auto; background: #0a0a0a; border: 1px solid #222; border-top: 2px solid #c5a059; padding: 30px; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        h2 { font-family: 'Cinzel', serif; color: #c5a059; margin-top: 0; display: flex; justify-content: space-between; align-items: center; }
        .user-badge { font-size: 12px; color: #666; font-family: sans-serif; text-transform: uppercase; letter-spacing: 1px; }
        .shard-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .shard-table th { text-align: left; padding: 12px; color: #555; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #222; letter-spacing: 1px; }
        .shard-table td { padding: 15px 12px; border-bottom: 1px solid #111; font-size: 14px; vertical-align: middle; }
        .srv-name { color: #fff; font-weight: bold; font-family: 'Cinzel', serif; }
        .identifier-tag { font-family: 'Roboto Mono', monospace; font-size: 11px; color: #c5a059; }
        .col-label { font-size: 8px; color: #333; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 2px; }
        .key-box { font-family: 'Roboto Mono', monospace; font-size: 10px; background: #000; padding: 4px 8px; border: 1px solid #111; display: block; margin-bottom: 5px; }
        .key-box.api  { color: #444; }
        .key-box.hash { color: #3a8fd8; border-color: #0a2a4a; }
        .key-box.hash-empty { color: #2a2a2a; border-color: #111; font-style: italic; }
        .zip-modified { font-family: 'Roboto Mono', monospace; font-size: 10px; color: #3a8fd8; background: #000; padding: 4px 8px; border: 1px solid #0a2a4a; display: block; }
        .zip-modified i { margin-right: 4px; }
        .zip-unknown { font-family: 'Roboto Mono', monospace; font-size: 10px; color: #333; background: #000; padding: 4px 8px; border: 1px solid #111; display: block; }
        .status-tag { font-size: 9px; font-weight: bold; padding: 3px 8px; border-radius: 2px; letter-spacing: 1px; }
        .tag-active { background: rgba(0,255,0,0.1); color: #00ff00; border: 1px solid rgba(0,255,0,0.2); }
        .tag-pending { background: rgba(255,170,0,0.1); color: #ffaa00; border: 1px solid rgba(255,170,0,0.2); }
        .action-link { color: #c5a059; text-decoration: none; font-size: 12px; margin-right: 15px; transition: 0.3s; }
        .action-link:hover { color: #fff; }
        .delete-link { color: #ff4444; text-decoration: none; font-size: 12px; }
        .btn-add { display: inline-block; margin-top: 30px; padding: 12px 25px; background: transparent; border: 1px solid #c5a059; color: #c5a059; text-decoration: none; font-family: 'Cinzel'; font-size: 12px; transition: 0.3s; }
        .btn-add:hover { background: #c5a059; color: #000; }
        .nav-back { display: block; margin-bottom: 20px; color: #444; text-decoration: none; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="manager-window">
    <a href="index.php" class="nav-back"><i class="fas fa-chevron-left"></i> Back to Portal</a>
    <h2>
        Shard Management
        <span class="user-badge"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?></span>
    </h2>
    <table class="shard-table">
        <thead>
            <tr>
                <th>Shard Name</th>
                <th>Identifier</th>
                <th>Status</th>
                <th>API Key / ZIP Hash / ZIP Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($shards as $s):
                $zip_modified = getZipLastModified($s['client_zip_url'] ?? '');
            ?>
            <tr>
                <td><span class="srv-name"><?php echo htmlspecialchars($s['server_name']); ?></span></td>
                <td><span class="identifier-tag">/<?php echo htmlspecialchars($s['shard_name'] ?? 'None'); ?>/</span></td>
                <td>
                    <?php if($s['is_active']): ?>
                        <span class="status-tag tag-active">LIVE</span>
                    <?php else: ?>
                        <span class="status-tag tag-pending">PENDING</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="col-label">API Key</span>
                    <span class="key-box api"><?php echo htmlspecialchars($s['api_key']); ?></span>

                    <span class="col-label">ZIP Hash</span>
                    <?php if(!empty($s['client_zip_hash'])): ?>
                        <span class="key-box hash"><?php echo htmlspecialchars($s['client_zip_hash']); ?></span>
                    <?php else: ?>
                        <span class="key-box hash-empty">— no hash stored —</span>
                    <?php endif; ?>

                    <span class="col-label">ZIP Last Modified</span>
                    <?php if($zip_modified): ?>
                        <span class="zip-modified"><i class="fas fa-archive"></i> <?php echo $zip_modified; ?></span>
                    <?php else: ?>
                        <span class="zip-unknown"><i class="fas fa-question-circle"></i> Unreachable</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <a href="shard_edit.php?id=<?php echo $s['id']; ?>" class="action-link"><i class="fas fa-edit"></i> Edit</a>
                    <a href="?del=<?php echo $s['id']; ?>" class="delete-link" onclick="return confirm('Eradicate this shard?')"><i class="fas fa-trash-alt"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="text-align: center;">
        <a href="server_add.php" class="btn-add"><i class="fas fa-plus-circle"></i> Register New Shard</a>
    </div>
</div>
</body>
</html>
