<?php
/**
 * Aldhran Freeshard - Admin Log View
 * Version: 1.1.0 - Pagination Support
 */
if (!isset($can_edit) || !$can_edit) return;

// --- PAGINATION LOGIK ---
$limit = 20; // Einträge pro Seite
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Gesamtzahl der Logs ermitteln
$total_res = $conn->query("SELECT COUNT(id) AS total FROM admin_logs");
$total_rows = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Logs für die aktuelle Seite holen
$log_query = $conn->query("
    SELECT al.*, u.username 
    FROM admin_logs al 
    LEFT JOIN users u ON al.admin_id = u.id 
    ORDER BY al.id DESC 
    LIMIT $limit OFFSET $offset
");
?>

<div class="admin-container">
    <h2 style="font-family:'Cinzel'; color:var(--gold); margin-bottom: 20px;">System Audit Logs</h2>

    <div class="admin-box" style="background:rgba(15,15,15,0.8); padding:20px; border-left:3px solid var(--gold); margin-bottom:10px;">
        <table style="width:100%; border-collapse: collapse;">
            <thead style="background:#111; color:#555; font-size:0.75em; text-transform:uppercase;">
                <tr>
                    <th style="padding:15px; text-align:left;">ID</th>
                    <th style="text-align:left;">Admin User</th>
                    <th style="text-align:left;">Action Type</th>
                    <th style="text-align:left;">Details</th>
                    <th style="text-align:right; padding-right:15px;">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($log_query && $log_query->num_rows > 0): 
                    while($l = $log_query->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #222; transition: background 0.2s;" onmouseover="this.style.background='rgba(212,175,55,0.03)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:15px; color:#555; font-size:0.85em;">#<?php echo $l['id']; ?></td>
                    <td style="font-weight:bold; color:#fff;">
                        <?php echo htmlspecialchars($l['username'] ?? 'System (ID: '.$l['admin_id'].')'); ?>
                    </td>
                    <td style="color:var(--gold); font-size:0.9em; font-weight:bold;">
                        <?php echo htmlspecialchars($l['action_type'] ?? 'N/A'); ?>
                    </td>
                    <td style="color:#aaa; font-size:0.85em; max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($l['details'] ?? ''); ?>
                    </td>
                    <td style="text-align:right; padding-right:15px; color:#666; font-size:0.8em; font-style:italic;">
                        <?php echo $l['created_at'] ?? $l['timestamp'] ?? '---'; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:#444; font-style:italic;">
                        The chronicles are empty. No admin actions recorded yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="display:flex; justify-content: center; gap: 10px; margin-top: 20px;">
        <?php if ($page > 1): ?>
            <a href="?p=admin_log&page=<?php echo $page - 1; ?>" style="color:var(--gold); text-decoration:none; padding: 5px 10px; border: 1px solid #333;">&laquo; Prev</a>
        <?php endif; ?>

        <span style="color:#666; padding: 5px 10px;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

        <?php if ($page < $total_pages): ?>
            <a href="?p=admin_log&page=<?php echo $page + 1; ?>" style="color:var(--gold); text-decoration:none; padding: 5px 10px; border: 1px solid #333;">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>