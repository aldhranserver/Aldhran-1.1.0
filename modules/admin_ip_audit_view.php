<?php
/**
 * ADMIN TOOL - IP AUDITOR V2.0
 * Location: /modules/admin_ip_audit.php
 * Version: 2.0.0 - SECURITY: PDO Migration & CSRF Protection
 */
require_once('includes/db.php');

if ($userPriv < 4) { die("Access Denied."); } // Nur für Staff

// --- 1. LOGIK: IP WHITELISTEN (PDO Syntax) ---
if (isset($_POST['approve_ip']) && !empty($_POST['ip_to_approve'])) {
    // Enterprise V2: CSRF Check
    checkToken($_POST['csrf_token'] ?? '');

    $ip_to_save = $_POST['ip_to_approve'];
    $admin_id   = $_SESSION['user_id'] ?? 0;
    $admin_name = $_SESSION['username'] ?? 'System';
    
    // PDO Insert (Sicher gegen SQL Injection)
    $stmt_ins = $db->prepare("INSERT IGNORE INTO household_registrations (ip_address, approved_by, reason) VALUES (?, ?, 'Manual GM Approval')");
    
    if ($stmt_ins->execute([$ip_to_save, $admin_name])) {
        // ENTERPRISE LOGGING: Wir halten fest, wer die IP freigegeben hat
        aldhran_log("IP_APPROVED", "GM $admin_name approved IP: $ip_to_save", $admin_id);
        
        header("Location: index.php?p=admin_ip_audit&msg=approved");
        exit;
    }
}

// --- 2. ABFRAGE DER DOPPELTEN IPs ---
$audit_sql = "
    SELECT LastLoginIP, COUNT(Account_ID) as AccountCount, GROUP_CONCAT(Name SEPARATOR ', ') as AccountNames
    FROM account 
    WHERE LastLoginIP != '' AND LastLoginIP != '127.0.0.1'
    GROUP BY LastLoginIP
    HAVING AccountCount > 1
";
$stmt_audit = $db->query($audit_sql);
$results = $stmt_audit->fetchAll();

echo '<div class="admin-container">';
    echo '<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">';
        echo '<h2 style="font-family:\'Cinzel\'; color:var(--gold); margin:0;">Household & IP Audit</h2>';
        echo '<span style="font-size: 11px; color: #444; letter-spacing: 1px;">STATUTE 1 ENFORCEMENT</span>';
    echo '</div>';

    echo '<table class="spawn-table">';
    echo '<thead><tr><th>IP Address</th><th>Count</th><th>Detected Accounts</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    if ($results) {
        foreach ($results as $row) {
            $ip = $row['LastLoginIP'];
            
            // Check gegen die CMS-Tabelle via PDO
            $stmt_check = $db->prepare("SELECT approved_by FROM household_registrations WHERE ip_address = ?");
            $stmt_check->execute([$ip]);
            $reg_data = $stmt_check->fetch();
            
            $is_registered = (bool)$reg_data;
            $status_style = $is_registered ? 'color:#4caf50;' : 'color:#f44336; font-weight:bold;';
            $status_text = $is_registered ? '<i class="fas fa-check-shield"></i> APPROVED' : '<i class="fas fa-exclamation-triangle"></i> VIOLATION';

            echo "<tr>";
                echo "<td><code style='color:#eee;'>" . h($ip) . "</code></td>";
                echo "<td style='text-align:center;'><strong>" . (int)$row['AccountCount'] . "</strong></td>";
                echo "<td><span style='font-size:10px; color:#aaa;'>" . h($row['AccountNames']) . "</span></td>";
                echo "<td style='$status_style'>$status_text</td>";
                echo "<td>";
                    if (!$is_registered) {
                        echo "<form method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='csrf_token' value='" . generateToken() . "'>";
                        echo "<input type='hidden' name='ip_to_approve' value='" . h($ip) . "'>";
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