<?php
/**
 * ADMIN TOOL - IP AUDITOR V1.1
 * Location: /modules/admin_ip_audit.php
 */
require_once('includes/db.php');

// --- 1. LOGIK: IP WHITELISTEN ---
if (isset($_POST['approve_ip']) && !empty($_POST['ip_to_approve'])) {
    $ip_to_save = mysqli_real_escape_string($conn, $_POST['ip_to_approve']);
    $admin_name = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'System');
    
    $conn->query("INSERT IGNORE INTO household_registrations (ip_address, approved_by, reason) 
                  VALUES ('$ip_to_save', '$admin_name', 'Manual GM Approval')");
    
    // Seite neu laden, um Änderungen zu sehen
    header("Location: index.php?p=admin_ip_audit&msg=approved");
    exit;
}

// --- 2. ABFRAGE DER DOPPELTEN IPs ---
$audit_sql = "
    SELECT LastLoginIP, COUNT(Account_ID) as AccountCount, GROUP_CONCAT(Name SEPARATOR ', ') as AccountNames
    FROM account 
    WHERE LastLoginIP != '' AND LastLoginIP != '127.0.0.1'
    GROUP BY LastLoginIP
    HAVING AccountCount > 1
";
$res = $conn->query($audit_sql);

echo '<div class="admin-container">';
    echo '<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">';
        echo '<h2 style="font-family:\'Cinzel\'; color:var(--gold); margin:0;">Household & IP Audit</h2>';
        echo '<span style="font-size: 11px; color: #444; letter-spacing: 1px;">STATUTE 1 ENFORCEMENT</span>';
    echo '</div>';

    echo '<table class="spawn-table">';
    echo '<thead><tr><th>IP Address</th><th>Count</th><th>Detected Accounts</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $ip = $row['LastLoginIP'];
            
            // Check gegen die CMS-Tabelle
            $check_reg = $conn->query("SELECT approved_by FROM household_registrations WHERE ip_address = '$ip'");
            $is_registered = ($check_reg && $check_reg->num_rows > 0);
            
            $status_style = $is_registered ? 'color:#4caf50;' : 'color:#f44336; font-weight:bold;';
            $status_text = $is_registered ? '<i class="fas fa-check-shield"></i> APPROVED' : '<i class="fas fa-exclamation-triangle"></i> VIOLATION';

            echo "<tr>";
                echo "<td><code style='color:#eee;'>$ip</code></td>";
                echo "<td style='text-align:center;'><strong>" . $row['AccountCount'] . "</strong></td>";
                echo "<td><span style='font-size:10px; color:#aaa;'>" . $row['AccountNames'] . "</span></td>";
                echo "<td style='$status_style'>$status_text</td>";
                echo "<td>";
                    if (!$is_registered) {
                        echo "<form method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='ip_to_approve' value='$ip'>";
                        echo "<button type='submit' name='approve_ip' class='btn-gold' style='padding:4px 8px; font-size:10px; margin-right:5px; cursor:pointer;'>APPROVE IP</button>";
                        echo "</form>";
                    }
                    echo "<button style='padding:4px 8px; font-size:10px; background:#1a1a1a; color:#888; border:1px solid #333;'>LOGS</button>";
                echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:#444;'>No IP overlaps detected.</td></tr>";
    }

    echo '</tbody></table>';
echo '</div>';
?>