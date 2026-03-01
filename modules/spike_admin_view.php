<?php 
/**
 * SPIKE ADMIN VIEW - Aldhran Enterprise
 * Version: 3.0.0 - SECURITY: PDO Migration & XSS Protection
 */
if (!defined('IN_CMS')) { exit; } 
?>

<style>
    .category-details { display: none; overflow: hidden; background: rgba(0,0,0,0.2); }
    .category-details.active { display: block; }
    .cat-header-clickable { cursor: pointer; transition: background 0.2s; }
    .cat-header-clickable:hover { background: rgba(255,255,255,0.06) !important; }
    .rotate-icon { transition: 0.3s; }
    .active-icon { transform: rotate(90deg); }
    .perm-input { width: 35px; background: #000; border: 1px solid #333; color: var(--glow-gold); text-align: center; font-size: 10px; padding: 2px; }
    .matrix-row { display: grid; grid-template-columns: 1fr 80px 80px; gap: 10px; padding: 8px 15px; border-bottom: 1px solid #111; align-items: center; }
    .nexus-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:9999; padding:5vh 20px; }
    .nexus-window { max-width:700px; margin:auto; border: 1px solid #333; background:#080808; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
    .dragging { opacity: 0.4; border: 1px dashed var(--glow-gold) !important; }
    .drag-handle { cursor: grab; color: #444; margin-right: 10px; }
</style>

<div class="um-nexus-wrapper">
    <div class="um-internal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
        <h2 class="um-internal-title"><i class="fas fa-hammer"></i> Spike Admin Panel</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 40px;">
        <div class="admin-box" style="padding:15px; border-left: 2px solid #555; background: rgba(0,0,0,0.3); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:9px; color:#888; font-family:'Cinzel';">Prevention / Purge</span>
            <button onclick="openOverlay('rfp-overlay')" class="btn-nexus-edit" style="font-size:8px;">EDIT</button>
        </div>
        <div class="admin-box" style="padding:15px; border-left: 2px solid var(--glow-blue); background: rgba(0,0,0,0.3); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:9px; color:#888; font-family:'Cinzel';">Reload Postcount</span>
            <button onclick="openOverlay('masf-overlay')" class="btn-nexus-edit" style="font-size:8px;">EDIT</button>
        </div>
        <div class="admin-box" style="padding:15px; border-left: 2px solid var(--glow-gold); background: rgba(0,0,0,0.3); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:9px; color:#888; font-family:'Cinzel';">Permissions</span>
            <button onclick="openOverlay('matrix-overlay')" class="btn-nexus-edit" style="font-size:8px;">EDIT</button>
        </div>
        <div class="admin-box" style="padding:15px; border-left: 2px solid var(--glow-gold); background: rgba(0,0,0,0.3); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:9px; color:#888; font-family:'Cinzel';">Categories & Forums</span>
            <button onclick="openOverlay('architect-overlay')" class="btn-nexus-edit" style="font-size:8px;">EDIT</button>
        </div>
    </div>

    <div id="matrix-overlay" class="nexus-overlay">
        <div class="nexus-window" style="border-color:var(--glow-gold); max-width:800px;">
            <div style="padding:15px; background:rgba(212,175,55,0.05); display:flex; justify-content:space-between;"><span>Access Matrix</span><button onclick="closeOverlay('matrix-overlay')" style="color:#555; background:none; border:none; cursor:pointer;"><i class="fas fa-times"></i></button></div>
            <form id="matrix-ajax-form">
                <div style="max-height:60vh; overflow-y:auto;">
                    <?php foreach($all_cats as $mc): ?>
                        <div class="matrix-row" style="background:rgba(212,175,55,0.05);">
                            <div style="color:var(--glow-gold); font-weight:bold;"><?php echo h($mc['title']); ?></div>
                            <div><input type="number" name="cat_perms[<?php echo $mc['id']; ?>][v]" value="<?php echo (int)$mc['min_priv']; ?>" class="perm-input"></div>
                            <div><input type="number" name="cat_perms[<?php echo $mc['id']; ?>][p]" value="<?php echo (int)$mc['min_priv_post']; ?>" class="perm-input"></div>
                        </div>
                        <?php 
                        $stmt_b = $db->prepare("SELECT * FROM spike_boards WHERE cat_id = ? ORDER BY pos ASC");
                        $stmt_b->execute([$mc['id']]);
                        while($mb = $stmt_b->fetch()): ?>
                            <div class="matrix-row">
                                <div style="padding-left:20px; color:#aaa; font-size:11px;"><?php echo h($mb['title']); ?></div>
                                <div><input type="number" name="board_perms[<?php echo $mb['id']; ?>][v]" value="<?php echo (int)$mb['min_priv']; ?>" class="perm-input"></div>
                                <div><input type="number" name="board_perms[<?php echo $mb['id']; ?>][p]" value="<?php echo (int)$mb['min_priv_post']; ?>" class="perm-input"></div>
                            </div>
                        <?php endwhile; ?>
                    <?php endforeach; ?>
                </div>
                <div style="padding:15px; text-align:right;"><span id="matrix-status" style="font-size:10px; margin-right:10px; font-family:'Cinzel';"></span><button type="button" onclick="ajaxCall('matrix-ajax-form', 'update_matrix')" class="btn-nexus-edit">PUSH CHANGES</button></div>
            </form>
        </div>
    </div>

    <div id="architect-overlay" class="nexus-overlay">
        <div class="nexus-window" style="border-color:#fff; max-width:800px;">
            <div style="padding:15px; background:rgba(255,255,255,0.05); display:flex; justify-content:space-between;"><span>Structure Architect</span><button onclick="closeOverlay('architect-overlay')" style="color:#555; background:none; border:none; cursor:pointer;"><i class="fas fa-times"></i></button></div>
            <div style="padding:20px; max-height:70vh; overflow-y:auto;">
                <div class="admin-box" style="padding:15px; margin-bottom:20px; border:1px solid #222;">
                    <form method="POST" style="display:flex; gap:10px;">
                        <input type="text" name="cat_title" placeholder="New Category Name..." class="um-input-search-glow" required>
                        <button type="submit" name="add_cat" class="btn-nexus-edit">CREATE CAT</button>
                    </form>
                </div>
                <?php foreach($all_cats as $mc): ?>
                    <div style="background:rgba(255,255,255,0.02); padding:15px; margin-bottom:15px; border:1px solid #111;">
                        <strong style="color:var(--glow-gold); font-size:11px; font-family:'Cinzel';"><?php echo h($mc['title']); ?></strong>
                        <form method="POST" style="margin-top:10px; display:grid; grid-template-columns: 1fr 2fr 100px; gap:10px;">
                            <input type="hidden" name="target_cat_id" value="<?php echo $mc['id']; ?>">
                            <input type="text" name="board_title" placeholder="Board Title..." class="um-input-search-glow" style="font-size:10px;" required>
                            <input type="text" name="board_desc" placeholder="Description..." class="um-input-search-glow" style="font-size:10px;">
                            <button type="submit" name="add_subforum" class="btn-nexus-edit" style="font-size:9px;">ADD BOARD</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="category-sort-container">
    <?php foreach($all_cats as $cat): ?>
        <div class="cat-wrapper admin-box" draggable="true" data-id="<?php echo $cat['id']; ?>" style="margin-bottom:15px; border-left: 2px solid var(--glow-gold); background:rgba(0,0,0,0.4); padding:0;">
            <div class="cat-header-clickable" onclick="toggleCategory(<?php echo $cat['id']; ?>)" style="padding:15px; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center;">
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    <i class="fas fa-chevron-right rotate-icon" id="icon-<?php echo $cat['id']; ?>"></i>
                    <strong style="color:var(--glow-gold); font-family:'Cinzel'; margin-left:10px;"><?php echo h($cat['title']); ?></strong>
                </div>
                <div onclick="event.stopPropagation();"><a href="index.php?p=spike_admin&del_cat=<?php echo $cat['id']; ?>" style="color:#444;" onclick="return confirm('Eradicate Category?')"><i class="fas fa-trash-alt"></i></a></div>
            </div>
            <div id="details-<?php echo $cat['id']; ?>" class="category-details">
                <div style="padding:20px; border-top:1px solid #222;">
                    <div class="board-sort-container" data-catid="<?php echo $cat['id']; ?>">
                        <?php 
                        $stmt_boards = $db->prepare("SELECT * FROM spike_boards WHERE cat_id = ? ORDER BY pos ASC");
                        $stmt_boards->execute([$cat['id']]);
                        while($b = $stmt_boards->fetch()): ?>
                            <div class="board-item" draggable="true" data-id="<?php echo $b['id']; ?>" style="display:flex; justify-content:space-between; padding:8px; border-bottom:1px solid #111; background:rgba(255,255,255,0.01);">
                                <div><i class="fas fa-grip-lines drag-handle" style="font-size:10px; opacity:0.3;"></i><span style="color:#aaa; font-size:11px;"><?php echo h($b['title']); ?></span></div>
                                <a href="index.php?p=spike_admin&del_board=<?php echo $b['id']; ?>" style="color:#222;" onclick="return confirm('Eradicate Board?')"><i class="fas fa-times"></i></a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<script>
/* JS Logik bleibt identisch, da sie mit der spike_admin_logic kommuniziert */
function toggleCategory(id) { document.getElementById('details-'+id).classList.toggle('active'); document.getElementById('icon-'+id).classList.toggle('active-icon'); }
function openOverlay(id) { document.getElementById(id).style.display = 'block'; }
function closeOverlay(id) { document.getElementById(id).style.display = 'none'; }

function ajaxCall(formId, action) {
    const status = (action === 'update_matrix') ? document.getElementById('matrix-status') : document.getElementById('masf-status');
    const formData = formId ? new FormData(document.getElementById(formId)) : new FormData();
    formData.append('ajax_action', action);
    status.innerHTML = "SENDING..."; status.style.color = "var(--glow-blue)";
    fetch('index.php?p=spike_admin', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(data => { 
        if(data.toLowerCase().includes('success')) { 
            status.innerHTML = "COMPLETED"; status.style.color = "var(--glow-gold)";
            setTimeout(() => { status.innerHTML = ""; }, 2000); 
        } else { status.innerHTML = "ERROR"; status.style.color = "orange"; }
    });
}
</script>