<?php 
/**
 * UM EDITOR VIEW - Aldhran Enterprise
 * Version: 2.1.5 - RESTORED SIGNATURE | ALL FIELDS INTACT | FIX: Data Save Bridge
 */
if (!isset($u_data)) return; 

$myPriv = (int)($_SESSION['priv_level'] ?? 0);
$targetPriv = (int)$u_data['priv_level'];
$view_token = generateToken(); 

$isStaffOnly = ($myPriv === 3);
$lockAttr = $isStaffOnly ? ' disabled ' : '';
$lockStyle = $isStaffOnly ? ' opacity: 0.7; cursor: not-allowed; ' : '';
?>
<div class="um-editor-container" style="animation: fadeIn 0.3s ease; background: rgba(0,0,0,0.4); padding: 25px; border-radius: 15px; border: 1px solid rgba(0, 212, 255, 0.1);">
    
    <div class="um-internal-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="um-internal-title" style="color: var(--glow-blue); margin: 0; text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);">
                Edit: <?php echo htmlspecialchars($u_data['username']); ?>
            </h2>
            <span style="color: #666; font-family: monospace; font-size: 0.8em;">UUID: <?php echo $u_data['id']; ?> | BS: <?php echo $targetPriv; ?></span>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <?php if ($myPriv >= 4 && !($myPriv == 4 && $targetPriv >= 5)): ?>
            <button type="button" 
                onclick="if(confirm('User <?php echo addslashes($u_data['username']); ?> wirklich löschen?')){ 
                    const fd = new FormData();
                    fd.append('um_action', 'delete_user');
                    fd.append('target_id', '<?php echo $u_data['id']; ?>');
                    fd.append('csrf_token', '<?php echo $view_token; ?>');
                    fd.append('can_edit', '1');
                    fetch('modules/um_sync_worker.php', { method: 'POST', body: fd })
                    .then(r => r.text()).then(t => { if(t.trim()==='SUCCESS'){ loadCategory('all'); }else{ alert(t); } });
                }" class="btn-nexus-edit" style="font-size: 0.7em; background: rgba(255, 77, 77, 0.1); border-color: var(--error-red); color: var(--error-red);">
                DELETE USER
            </button>
            <?php endif; ?>
        </div>
    </div>

    <form id="um_main_edit_form" 
          onsubmit="event.preventDefault(); 
                    const fd = new FormData(this); 
                    fd.append('csrf_token', '<?php echo $view_token; ?>');
                    fd.append('can_edit', '1');
                    fetch('modules/um_sync_worker.php', { method: 'POST', body: fd })
                    .then(r => r.text())
                    .then(t => { loadUserEditor(<?php echo $u_data['id']; ?>); });" 
          enctype="multipart/form-data">
        
        <input type="hidden" name="target_id" value="<?php echo $u_data['id']; ?>">
        <input type="hidden" name="um_action" value="update_full">

        <div class="um-quick-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="um-editor-left">
                <div class="quick-card" style="text-align: center; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                    <label style="display: block; margin-bottom: 10px; color: #aaa; font-size: 0.9em;">Profile Image</label>
                    <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                        <div <?php echo !$isStaffOnly ? 'onclick="document.getElementById(\'u_avatar\').click();"' : ''; ?> style="width: 100%; height: 100%; border-radius: 50%; border: 2px dashed var(--glow-blue); overflow: hidden; background: #000; <?php echo !$isStaffOnly ? 'cursor: pointer;' : 'cursor: default; opacity: 0.6;'; ?> display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($u_data['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($u_data['avatar_url']); ?>?t=<?php echo time(); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="color: var(--glow-blue); font-size: 2.5em;">+</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$isStaffOnly): ?>
                    <input type="file" name="u_avatar" id="u_avatar" style="display: none;" accept="image/*" onchange="document.getElementById('um_main_edit_form').requestSubmit();">
                    <?php endif; ?>
                </div>

                <div class="quick-card" style="text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 2px solid var(--glow-gold);">
                    <label style="display: block; margin-bottom: 5px; color: var(--glow-gold);">Change Password</label>
                    <input type="password" name="u_new_password" class="um-input-search-glow" <?php echo $lockAttr; ?> placeholder="Leave empty to keep" style="width: 100%; <?php echo $lockStyle; ?>">
                </div>

                <div class="quick-card" style="text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 2px solid var(--glow-blue);">
                    <label style="display: block; margin-bottom: 5px; color: var(--glow-blue);">CMS Staff Title</label>
                    <input type="text" name="u_title" value="<?php echo htmlspecialchars($u_data['user_title'] ?? ''); ?>" <?php echo $lockAttr; ?> class="um-input-search-glow" style="width: 100%; <?php echo $lockStyle; ?>">
                </div>
            </div>

            <div class="um-editor-right">
                <div class="quick-card" style="text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                    <label style="display: block; margin-bottom: 5px; color: #aaa;">Privilege Level (CMS)</label>
                    <select name="u_priv" class="um-input-search-glow" style="width: 100%; <?php echo $lockStyle; ?>" <?php echo $lockAttr; ?>>
                        <?php for($i=1; $i<=5; $i++) echo "<option value='$i'".($u_data['priv_level']==$i?' selected':'').">Level $i</option>"; ?>
                    </select>
                </div>

                <div class="quick-card" style="text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 2px solid #9b59b6;">
                    <label style="display: block; margin-bottom: 5px; color: #9b59b6;">Ingame PrivLevel (DOL)</label>
                    <select name="u_ingame_priv" class="um-input-search-glow" style="width: 100%; <?php echo $lockStyle; ?>" <?php echo $lockAttr; ?>>
                        <option value="1" <?php echo ($u_data['ingame_priv'] == 1 ? 'selected' : ''); ?>>1 - Player</option>
                        <option value="2" <?php echo ($u_data['ingame_priv'] == 2 ? 'selected' : ''); ?>>2 - GM</option>
                        <option value="3" <?php echo ($u_data['ingame_priv'] == 3 ? 'selected' : ''); ?>>3 - Admin</option>
                    </select>
                </div>

                <div class="quick-card" style="text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #aaa;">Standing</label>
                    <select name="u_stand" id="u_stand_select" class="um-input-search-glow" onchange="document.getElementById('reason_box').style.display=(this.value>0?'block':'none');" style="width: 100%;">
                        <?php for($i=0; $i<=5; $i++) echo "<option value='$i'".($u_data['standing']==$i?' selected':'').">$i - ".getStandingText($i)."</option>"; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="quick-card" style="margin-top: 20px; text-align: left; background: rgba(0, 212, 255, 0.03); border: 1px solid rgba(0, 212, 255, 0.2); padding: 15px; border-radius: 8px;">
            <label style="color: var(--glow-blue); display: block; margin-bottom: 10px; font-family: 'Cinzel'; font-size: 0.9em;">
                <i class="fas fa-signature"></i> Spike Forum Signature
            </label>
            <textarea name="forum_signature" class="um-input-search-glow" <?php echo $lockAttr; ?> style="width: 100%; height: 80px; resize: none; <?php echo $lockStyle; ?>"><?php echo htmlspecialchars($u_data['forum_signature'] ?? ''); ?></textarea>
        </div>

        <div id="reason_box" class="quick-card" style="margin-top: 20px; text-align: left; border: 1px solid var(--glow-gold); background: rgba(218, 165, 32, 0.05); padding: 15px; display: <?php echo ($u_data['standing'] > 0 ? 'block' : 'none'); ?>;">
            <label style="color: var(--glow-gold); display: block; margin-bottom: 5px;">Standing Reason</label>
            <textarea name="u_reason" class="um-input-search-glow" style="width: 100%; height: 60px;"><?php echo htmlspecialchars($u_data['standing_reason'] ?? ''); ?></textarea>
        </div>

        <div class="quick-card" style="margin-top: 20px; text-align: left; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
            <label style="display: block; margin-bottom: 5px; color: #aaa; font-size: 0.9em;">User Biography</label>
            <textarea name="u_bio" <?php echo $lockAttr; ?> class="um-input-search-glow" style="width: 100%; height: 100px; resize: vertical; <?php echo $lockStyle; ?>"><?php echo htmlspecialchars($u_data['biography'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between;">
            <button type="button" onclick="loadCategory('all')" style="background:none; border:none; color:#555; cursor:pointer;">← BACK</button>
            <button type="submit" class="btn-add-user" style="width: 180px;">SAVE CHANGES</button>
        </div>
    </form>
</div>