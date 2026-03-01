<?php
/**
 * PVE MAIN DASHBOARD - Aldhran V1.0 - Standalone
 */
require_once('includes/db.php');

// Nutze $conn statt $db und hole Statistiken aus der DOL-Datenbank
$stats_mobs   = $conn->query("SELECT COUNT(*) FROM mob")->fetch_row()[0] ?? 0;
$stats_items  = $conn->query("SELECT COUNT(*) FROM itemtemplate")->fetch_row()[0] ?? 0;
$stats_quests = $conn->query("SELECT COUNT(*) FROM quest")->fetch_row()[0] ?? 0;
?>

<style>
    .pve-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .pve-tile {
        background: linear-gradient(135deg, rgba(30,30,30,0.9) 0%, rgba(10,10,10,0.95) 100%);
        border: 1px solid rgba(197, 160, 89, 0.1);
        padding: 30px;
        text-align: center;
        text-decoration: none;
        transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border-radius: 4px;
        position: relative;
        overflow: hidden;
    }
    .pve-tile:hover {
        border-color: var(--gold);
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.6);
    }
    .pve-tile i {
        font-size: 3em;
        color: var(--gold);
        margin-bottom: 20px;
        opacity: 0.6;
        transition: 0.3s;
    }
    .pve-tile:hover i {
        opacity: 1;
        transform: scale(1.1);
    }
    .pve-tile h3 {
        font-family: 'Cinzel';
        color: #eee;
        margin: 10px 0;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .pve-tile p {
        font-size: 11px;
        color: #555;
        line-height: 1.6;
        margin-top: 10px;
    }
    .stat-overlay {
        position: absolute;
        bottom: 10px;
        right: 15px;
        font-size: 9px;
        color: rgba(197, 160, 89, 0.3);
        font-family: monospace;
    }
</style>

<div class="admin-container">
    <div class="pve-dashboard">
        <a href="?p=pve_bestiary" class="pve-tile">
            <i class="fas fa-dragon"></i>
            <h3>Bestiary</h3>
            <p>Consult the chronicles of manifested entities. Find strengths, weaknesses, and regional spawns.</p>
            <div class="stat-overlay"><?php echo number_format($stats_mobs); ?> ENTITIES</div>
        </a>

        <a href="?p=pve_dungeons" class="pve-tile">
            <i class="fas fa-dungeon"></i>
            <h3>Dungeons</h3>
            <p>Regional mapping of the darkest corners. View localized mission logs and creature density.</p>
            <div class="stat-overlay">6 KEY REGIONS</div>
        </a>

        <a href="?p=pve_quests" class="pve-tile">
            <i class="fas fa-scroll"></i>
            <h3>Quests</h3>
            <p>The archives of heroic deeds. Review objectives, kill-targets, and legendary rewards.</p>
            <div class="stat-overlay"><?php echo number_format($stats_quests); ?> CHRONICLES</div>
        </a>

        <a href="?p=pve_alchemy" class="pve-tile" style="grid-column: span 1;">
            <i class="fas fa-mortar-pestle"></i>
            <h3>Alchemist</h3>
            <p>Direct marketplace for elixirs and consumables. Real-time gold exchange with bag-injection.</p>
            <div class="stat-overlay">OPEN MARKET</div>
        </a>
    </div>

    <div style="margin-top: 60px; text-align: center; border-top: 1px solid #111; padding-top: 30px;">
        <p style="font-size: 10px; color: #333; font-style: italic;">
            "Knowledge is the sharpest blade in the struggle for Aldhran."
        </p>
    </div>
</div>