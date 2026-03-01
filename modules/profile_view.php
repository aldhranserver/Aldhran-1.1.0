<?php 
/**
 * PROFILE VIEW - Aldhran Freeshard
 * Version: 0.8.3 - CLEANED: phpBB references removed
 */
if (!isset($_SESSION['user_id'])) return; 

$uid = (int)$_SESSION['user_id'];
$me_view = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

$standing_map = [
    0 => ['label' => 'You are all good!', 'color' => '#00ff00'],
    1 => ['label' => 'First Warning', 'color' => '#ffff00'],
    2 => ['label' => 'Second Warning', 'color' => '#ffaa00'],
    3 => ['label' => 'Restricted', 'color' => '#ff6600'],
    4 => ['label' => 'Suspended', 'color' => '#ff0000'],
    5 => ['label' => 'Banned', 'color' => '#440000']
];

$s_val = (int)$me_view['standing'];
$cur_std = $standing_map[$s_val] ?? $standing_map[0];
$is_restricted = ($s_val >= 3); 
$standing_reason = $me_view['standing_reason'] ?? '';
?>

<div class="admin-container">
    <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197,160,89,0.1); padding: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $cur_std['color']; ?>; box-shadow: 0 0 10px <?php echo $cur_std['color']; ?>;"></div>
            <div>
                <span style="font-size: 0.7em; color: #555; text-transform: uppercase; letter-spacing: 1px; display: block;">Account Standing</span>
                <span style="font-family: 'Cinzel'; color: <?php echo $cur_std['color']; ?>; font-size: 1.1em;"><?php echo $cur_std['label']; ?></span>
            </div>
        </div>
    </div>

    <div class="admin-box" style="padding: 30px;">
        <form action="index.php?p=profile" method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 150px 1fr; gap: 30px;">
                
                <div style="text-align: center;">
                    <div style="margin-bottom: 15px; position: relative; display: inline-block;">
                        <?php if(!empty($me_view['avatar_url'])): ?>
                            <img src="<?php echo $me_view['avatar_url']; ?>" style="width: 120px; height: 120px; border: 1px solid var(--gold); object-fit: cover; <?php echo ($is_restricted) ? 'filter: grayscale(100%) opacity(0.6);' : ''; ?>">
                            <?php if (!$is_restricted): ?>
                                <a href="?p=profile&delete_my_avatar=1" style="position: absolute; top: -10px; right: -10px; background: #000; color: #ff4444; border: 1px solid #ff4444; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                                     <i class="fas fa-trash-alt" style="font-size: 12px;"></i>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; border: 1px solid #222; display: flex; align-items: center; justify-content: center; background: #050505;">
                                <i class="fas fa-user-circle" style="font-size: 80px; color: #111;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$is_restricted): ?>
                        <input type="file" name="avatar" id="av_upload" style="display: none;" onchange="this.form.submit();">
                        <label for="av_upload" class="btn-gold" style="font-size: 0.7em; padding: 5px 10px; cursor: pointer; display: block; width: 100px; margin: 0 auto;">Change Image</label>
                    <?php endif; ?>
                </div>

                <div>
                    <div style="margin-bottom: 20px;">
                        <label class="um-label">Languages</label>
                        <input type="text" name="u_langs" value="<?php echo htmlspecialchars($me_view['languages'] ?? ''); ?>" <?php echo ($is_restricted) ? 'readonly' : ''; ?> style="width: 100%;" class="um-input">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label class="um-label">Biography</label>
                        <textarea name="u_desc" <?php echo ($is_restricted) ? 'readonly' : ''; ?> style="width: 100%; height: 80px; resize: none;" class="um-input"><?php echo htmlspecialchars($me_view['description'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 20px; border-left: 2px solid var(--glow-blue); padding-left: 15px;">
                        <label class="um-label" style="color: var(--glow-blue);">Forum Signature</label>
                        <textarea name="u_sig" <?php echo ($is_restricted) ? 'readonly' : ''; ?> style="width: 100%; height: 60px; resize: none; background: rgba(0,212,255,0.02);" class="um-input"><?php echo htmlspecialchars($me_view['forum_signature'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 25px; border-top: 1px solid #111; padding-top: 20px;">
                        <label class="um-label">Change Password (Updates Game & Forum)</label>
                        <input type="password" name="new_pw" <?php echo ($is_restricted) ? 'readonly' : ''; ?> style="width: 100%;" class="um-input" placeholder="Leave empty to keep current">
                    </div>
                    
                    <?php if (!$is_restricted): ?>
                        <button type="submit" name="update_profile" class="btn-gold" style="width: 220px;">Update Profile</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>