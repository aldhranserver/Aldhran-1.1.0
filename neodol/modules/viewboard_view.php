<?php if (!defined('IN_CMS')) { exit; } ?>

<div class="um-nexus-wrapper">
    <div style="margin-bottom: 20px;">
        <a href="?p=spike" style="color: #555; text-decoration: none; font-size: 0.75em; text-transform: uppercase; letter-spacing: 1.5px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;" 
           onmouseover="this.style.color='var(--glow-blue)'; this.style.paddingLeft='5px';" 
           onmouseout="this.style.color='#555'; this.style.paddingLeft='0'">
            <i class="fas fa-chevron-left" style="font-size: 0.8em;"></i> Back to Forums
        </a>
    </div>

    <div class="um-internal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; gap: 20px;">
        <div style="flex: 1;">
            <h2 class="um-internal-title" style="margin: 0; font-family: 'Cinzel', serif; letter-spacing: 2px; color: #fff; text-transform: uppercase;">
                <?php echo h($board_info['title'] ?? 'Unknown Board'); ?>
            </h2>
        </div>
        
        <?php 
        $required_bs = (isset($board_info['min_priv_post']) && (int)$board_info['min_priv_post'] > 0) 
                     ? (int)$board_info['min_priv_post'] 
                     : (int)($board_info['cat_min_post'] ?? 1);

        if ($myId > 0 && $myStanding < 3 && $myPriv >= $required_bs): ?>
            <div>
                <a href="?p=newthread&bid=<?php echo (int)$board_id; ?>" 
                   style="text-decoration: none; background: rgba(212, 175, 55, 0.1); color: #d4af37; border: 1px solid #d4af37; padding: 6px 14px; font-size: 0.7em; font-weight: bold; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; text-transform: uppercase;"
                   onmouseover="this.style.background='#d4af37'; this.style.color='#000';"
                   onmouseout="this.style.background='rgba(212, 175, 55, 0.1)'; this.style.color='#d4af37';">
                    <i class="fas fa-plus" style="font-size: 0.9em;"></i> New Thread
                </a>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

    <div class="admin-box" style="background:rgba(10,10,10,0.9); padding:0; border-left:3px solid var(--glow-blue); overflow: hidden;">
        <table style="width:100%; border-collapse: collapse;">
            <thead style="background: #080808; color: #555; font-size: 0.7em; text-transform: uppercase; letter-spacing: 2px; border-bottom: 1px solid #1a1a1a;">
                <tr>
                    <?php if($myPriv >= 4): ?>
                        <th style="width:40px; padding-left:20px;">
                            <input type="checkbox" onclick="toggleAllThreads(this)" style="cursor:pointer;" title="Select all">
                        </th>
                    <?php endif; ?>
                    <th style="padding:20px; text-align:left; width: 55%;">Topic</th>
                    <th style="text-align:left;">Author</th>
                    <th style="text-align:right; padding-right:25px;">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // FIX: Nutzt jetzt das Array aus der PDO-Logik
                if (!empty($threads)): 
                    foreach($threads as $t): ?>
                <tr style="border-bottom:1px solid #151515; transition: 0.3s;" 
                    onmouseover="this.style.background='rgba(0, 212, 255, 0.04)'" 
                    onmouseout="this.style.background='transparent'">
                    
                    <?php if($myPriv >= 4): ?>
                    <td style="padding-left:20px;">
                        <input type="checkbox" name="selected_threads[]" value="<?php echo (int)$t['id']; ?>" class="thread-checkbox" style="cursor:pointer;">
                    </td>
                    <?php endif; ?>

                    <td style="padding:20px; cursor:pointer;" onclick="window.location.href='?p=viewthread&id=<?php echo (int)$t['id']; ?>'">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if($t['is_sticky']): ?><i class="fas fa-thumbtack" style="color:var(--glow-gold); font-size: 0.85em;"></i><?php endif; ?>
                            <?php if($t['is_locked']): ?><i class="fas fa-lock" style="color:#444; font-size: 0.85em;"></i><?php endif; ?>
                            <span style="color:#f0f0f0; font-weight:bold; font-size: 0.95em;"><?php echo h($t['title']); ?></span>
                        </div>
                    </td>
                    
                    <td style="padding:12px 0;">
                        <span style="color:var(--glow-blue); font-size:0.85em;"><?php echo h($t['username'] ?? 'Ghost'); ?></span>
                        <br><small style="color:#333; font-size:0.65em; text-transform: uppercase;"><?php echo h($t['user_title'] ?? 'Player'); ?></small>
                    </td>
                    
                    <td style="text-align:right; padding-right:25px; color:#555; font-size:0.8em;">
                        <?php echo date("d.m.Y", strtotime($t['created_at'])); ?>
                        <br><span style="font-size: 0.85em; opacity: 0.6;"><?php echo date("H:i", strtotime($t['created_at'])); ?></span>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="<?php echo ($myPriv >= 4) ? '4' : '3'; ?>" style="padding:70px 20px; text-align:center; color:#222;">
                        <span style="font-style: italic; font-size: 1.1em;">There are no posts yet.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($myPriv >= 4 && !empty($threads)): ?>
        <div style="background: #050505; padding: 15px 20px; display: flex; align-items: center; flex-wrap: wrap; gap: 15px; border-top: 1px solid #111;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shield-alt" style="color:var(--glow-blue); font-size: 0.9em;"></i>
                <span style="color:#555; font-size:0.75em; text-transform:uppercase; letter-spacing:1px; font-weight:bold;">Mod Actions:</span>
            </div>

            <select name="mod_batch_action" class="um-input" style="width:180px; padding:5px; font-size:0.8em; background:#111; border:1px solid #333; color:#ccc;">
                <option value="move">Move to Board...</option>
                <option value="delete">Delete Topics</option>
                <option value="toggle_lock">Lock / Unlock</option>
                <option value="toggle_sticky">Pin / Unpin</option>
            </select>

            <select name="target_board" class="um-input" style="width:180px; padding:5px; font-size:0.8em; background:#111; border:1px solid #333; color:#ccc;">
                <option value="0">-- Target Board --</option>
                <?php 
                // FIX: PDO Arrays nutzt man mit foreach
                foreach($all_boards as $ab): ?>
                    <option value="<?php echo (int)$ab['id']; ?>"><?php echo h($ab['title']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="execute_mod_action" class="btn-nexus-edit" style="padding: 5px 15px; font-size:0.7em; border-color: var(--glow-blue); color: var(--glow-blue);" onclick="return confirm('Execute selected moderation actions on all checked topics?')">
                APPLY ACTIONS
            </button>
        </div>
        <?php endif; ?>
    </div>
    </form>
</div>

<script>
function toggleAllThreads(source) {
    var checkboxes = document.getElementsByClassName('thread-checkbox');
    for(var i=0, n=checkboxes.length; i<n; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>