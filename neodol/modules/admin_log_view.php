<?php
/**
 * Aldhran Enterprise - Audit Log Viewer
 * Version: 2.0.0 - SECURITY: PDO Migration & Table Sync
 */
if (!isset($can_edit) || !$can_edit) return;

// --- 1. PAGINATION LOGIK (PDO) ---
$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Gesamtzahl der Logs ermitteln via PDO
// Wir nutzen jetzt die neue Tabelle aldhran_logs
$stmt_count = $db->query("SELECT COUNT(id) AS total FROM aldhran_logs");
$total_rows = $stmt_count->fetch()['total'];
$total_pages = ceil($total_rows / $limit);

// Logs für die aktuelle Seite holen
// Wir joinen die Users-Tabelle, um die Namen der Verursacher zu sehen
$stmt_logs = $db->prepare("
    SELECT al.*, u.username 
    FROM aldhran_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.id DESC 
    LIMIT :limit OFFSET :offset
");

// PDO braucht bei LIMIT/OFFSET oft explizite Integers
$stmt_logs->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt_logs->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll();
?>

<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-family:'Cinzel'; color:var(--gold); margin:0;">Audit Logs</h2>
        <span style="font-size: 10px; color: #444; letter-spacing: 2px;">V2.0 STABLE</span>
    </div>

    <div class="admin-box" style="background:rgba(10,10,10,0.9); padding:20px; border:1px solid #222; border-left:3px solid var(--gold);">
        <table style="width:100%; border-collapse: collapse;">
            <thead style="background:#000; color:#555; font-size:0.75em; text-transform:uppercase; letter-spacing:1px;">
                <tr>
                    <th style="padding:15px; text-align:left;">ID</th>
                    <th style="text-align:left;">Actor</th>
                    <th style="text-align:left;">Action</th>
                    <th style="text-align:left;">Target / Details</th>
                    <th style="text-align:right; padding-right:15px;">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs): 
                    foreach($logs as $l): ?>
                <tr style="border-bottom:1px solid #111; transition: background 0.2s;" onmouseover="this.style.background='rgba(212,175,55,0.03)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:15px; color:#444; font-size:0.85em;">#<?php echo $l['id']; ?></td>
                    <td style="font-weight:bold; color:#ccc;">
                        <?php echo h($l['username'] ?? 'System / Anonymous'); ?>
                    </td>
                    <td>
                        <span style="display:inline-block; padding:2px 8px; background:rgba(212,175,55,0.1); color:var(--gold); font-size:0.75em; border-radius:3px; font-weight:bold; border:1px solid rgba(212,175,55,0.2);">
                            <?php echo h($l['action_type']); ?>
                        </span>
                    </td>
                    <td style="color:#888; font-size:0.85em; max-width: 350px;">
                        <span style="color:#555; font-weight:bold;"><?php echo $l['target_id'] ? "[T:{$l['target_id']}] " : ""; ?></span>
                        <?php echo h($l['details']); ?>
                        <div style="font-size: 0.8em; color: #333; margin-top: 3px;">IP: <?php echo h($l['ip_address']); ?></div>
                    </td>
                    <td style="text-align:right; padding-right:15px; color:#555; font-size:0.8em; font-style:italic;">
                        <?php echo $l['created_at']; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:#333; font-style:italic;">
                        The chronicles are silent. No logs found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="display:flex; justify-content: center; align-items: center; gap: 15px; margin-top: 30px;">
        <?php if ($page > 1): ?>
            <a href="?p=admin_log&page=<?php echo $page - 1; ?>" style="color:var(--gold); text-decoration:none; padding: 8px 15px; border: 1px solid #222; background:#000; font-size:0.8em;">&laquo; PREV</a>
        <?php endif; ?>

        <span style="color:#444; font-size: 0.8em; text-transform:uppercase; letter-spacing:1px;">Page <?php echo $page; ?> / <?php echo $total_pages; ?></span>

        <?php if ($page < $total_pages): ?>
            <a href="?p=admin_log&page=<?php echo $page + 1; ?>" style="color:var(--gold); text-decoration:none; padding: 8px 15px; border: 1px solid #222; background:#000; font-size:0.8em;">NEXT &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>