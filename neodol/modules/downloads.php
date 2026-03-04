<?php
/**
 * NeoDOL 1.1.7 - Downloads Module (Clean Form)
 */
if (!defined('IN_CMS')) { exit; }

$cat = $_GET['cat'] ?? null;
$action = $_GET['action'] ?? '';
$show_form = ($action === 'add');

$categories = [
    'tools'   => ['icon' => 'fa-tools', 'title' => 'Tools & Utilities'],
    'scripts' => ['icon' => 'fa-code', 'title' => 'Scripts & Logic'],
    'assets'  => ['icon' => 'fa-file-archive', 'title' => 'Assets & Models'],
    'world'   => ['icon' => 'fa-globe', 'title' => 'World & Database']
];
?>

<style>
    .tile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
    .dl-tile { 
        background: #0a0a0a !important; border: 1px solid #1a1a1a !important; 
        padding: 40px 20px; text-align: center; text-decoration: none !important; 
        display: block; transition: 0.3s; border-radius: 2px;
    }
    .dl-tile:hover { border-color: #c5a059 !important; background: #111 !important; transform: translateY(-3px); }
    .dl-tile i { font-size: 3rem; color: #333; margin-bottom: 15px; display: block; }
    .dl-tile:hover i { color: #c5a059; }
    .dl-tile span { font-family: 'Cinzel', serif; color: #fff; font-size: 1.1rem; display: block; }
    .dl-tile small { color: #444; text-transform: uppercase; letter-spacing: 2px; font-size: 0.6rem; }

    .btn-gold { background: #c5a059; color: #000; padding: 10px 20px; font-family: 'Cinzel'; font-weight: bold; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
    .cinzel-header { border-bottom: 1px solid #222; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    
    .neodol-form { background: #0a0a0a; border: 1px solid #222; padding: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; color: #c5a059; font-family: 'Cinzel'; font-size: 0.8rem; margin-bottom: 5px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; background: #000; border: 1px solid #222; color: #ccc; padding: 10px; font-family: sans-serif; }
    
    .neodol-table { width: 100%; border-collapse: collapse; }
    .neodol-table th { color: #c5a059; text-align: left; border-bottom: 1px solid #222; padding: 10px; font-family: 'Cinzel'; font-size: 0.8rem; }
    .neodol-table td { padding: 10px; border-bottom: 1px solid #111; }
</style>

<div class="neodol-container">
    <?php if ($show_form): ?>
        <div class="cinzel-header">
            <h2 style="color:#c5a059; font-family:Cinzel;">SUBMIT CONTRIBUTION</h2>
            <a href="?p=downloads" style="color:#666; font-size:0.7rem;">CANCEL</a>
        </div>
        <form action="index.php?p=downloads&action=upload" method="POST" enctype="multipart/form-data" class="neodol-form">
            <div class="form-group">
                <label>Resource Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <?php foreach($categories as $id => $info): ?>
                        <option value="<?php echo $id; ?>"><?php echo $info['title']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="What does this file do?"></textarea>
            </div>
            <div class="form-group">
                <label>How to install</label>
                <textarea name="how_to_install" rows="3" placeholder="Instructions for integration..."></textarea>
            </div>
            <div class="form-group">
                <label>File Upload (.zip, .cs, .sql)</label>
                <input type="file" name="resource_file" required>
            </div>
            <button type="submit" class="btn-gold" style="width:100%">INITIALIZE ARCHIVE TRANSFER</button>
        </form>

    <?php elseif ($cat): ?>
        <div class="cinzel-header">
            <h2 style="color:#c5a059; font-family:Cinzel;"><?php echo $categories[$cat]['title']; ?></h2>
            <a href="?p=downloads" style="color:#666; font-size:0.7rem;">BACK TO CATEGORIES</a>
        </div>
        <table class="neodol-table">
            <thead><tr><th>Archive Item</th><th>Contributor</th><th>Action</th></tr></thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT * FROM neodol_downloads WHERE category = ? AND status = 'approved'");
                $stmt->execute([$cat]);
                while($f = $stmt->fetch()): ?>
                    <tr>
                        <td style="color:#fff"><?php echo safe_h($f['title']); ?></td>
                        <td>User #<?php echo $f['author_id']; ?></td>
                        <td><a href="uploads/downloads/<?php echo $f['file_path']; ?>" class="btn-gold" style="padding:4px 8px; font-size:0.6rem;" download>DOWNLOAD</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div class="cinzel-header">
            <h2 style="color:#c5a059; font-family:Cinzel;">ARCHIVE CATEGORIES</h2>
            <a href="?p=downloads&action=add" class="btn-gold">ADD CONTRIBUTION</a>
        </div>
        <div class="tile-grid">
            <?php foreach ($categories as $id => $info): ?>
                <a href="?p=downloads&cat=<?php echo $id; ?>" class="dl-tile">
                    <i class="fas <?php echo $info['icon']; ?>"></i>
                    <span><?php echo $info['title']; ?></span>
                    <small>Access Records</small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>