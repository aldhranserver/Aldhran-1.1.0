<?php
/**
 * PVE QUEST VIEW - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & Dynamic Filtering
 */
require_once('includes/db.php');

// 1. Filter-Parameter via PDO-Array vorbereiten
$search = $_GET['search'] ?? '';
$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "Name LIKE ?";
    $params[] = "%$search%";
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 2. Abfrage aus dataquest via PDO Prepared Statement
$stmt_quests = $db->prepare("
    SELECT ID, Name, MinLevel 
    FROM dataquest 
    $where_sql 
    ORDER BY MinLevel ASC 
    LIMIT 100
");
$stmt_quests->execute($params);
$quests = $stmt_quests->fetchAll();
?>

<style>
    .quest-list { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
    .quest-card {
        background: rgba(20, 20, 20, 0.6);
        border: 1px solid rgba(197, 160, 89, 0.1);
        padding: 15px 25px;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.3s;
    }
    .quest-card:hover { 
        border-color: var(--gold); 
        background: rgba(30, 30, 30, 0.8); 
        transform: translateX(5px); 
    }
    .quest-info h3 { 
        font-family: 'Cinzel'; 
        color: #ccc; 
        margin: 0; 
        font-size: 1.1em; 
        letter-spacing: 1px; 
    }
    .quest-info span { 
        font-size: 10px; 
        color: #555; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
    }
    .btn-view-quest { 
        background: transparent; 
        border: 1px solid #333; 
        color: #777; 
        padding: 6px 15px; 
        font-size: 10px; 
        text-decoration: none; 
        border-radius: 2px; 
    }
    .quest-card:hover .btn-view-quest { 
        border-color: var(--gold); 
        color: var(--gold); 
    }
</style>

<div class="admin-container">
    <h2 style="font-family:'Cinzel'; color:var(--gold); margin:0;">Quest Chronicles</h2>
    <p style="color:#444; font-size: 10px; text-transform: uppercase; margin-bottom: 25px;">Deciphering the Archives of Aldhran</p>

    <form method="GET" style="display:flex; gap:10px; margin-bottom: 30px;">
        <input type="hidden" name="p" value="pve_quests">
        <input type="text" name="search" value="<?php echo h($search); ?>" class="um-input" placeholder="Search chronicles..." style="flex:1;">
        <button type="submit" class="btn-gold" style="font-size:10px; padding:0 20px;">FILTER</button>
    </form>

    <div class="quest-list">
        <?php if ($quests): ?>
            <?php foreach($quests as $q): ?>
                <div class="quest-card">
                    <div class="quest-info">
                        <span>Level <?php echo (int)($q['MinLevel'] ?? 1); ?></span>
                        <h3><?php echo h($q['Name'] ?? 'Unknown Quest'); ?></h3>
                    </div>
                    <a href="?p=pve_quest_detail&id=<?php echo urlencode((string)$q['ID']); ?>" class="btn-view-quest">READ ARCHIVE</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #222; border: 1px dashed #111;">No entries found in the data archives.</div>
        <?php endif; ?>
    </div>
</div>