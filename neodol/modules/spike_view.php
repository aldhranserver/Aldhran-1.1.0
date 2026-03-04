<?php
/**
 * SPIKE FORUM VIEW - Aldhran Freeshard
 * Version: 0.4.2 - Currently Online (Minimalist Bottom)
 */
if (!defined('IN_CMS')) { exit; }

if (!isset($forum_structure)) {
    echo "<div class='info-msg'>Forum Data not found.</div>";
    return;
}
?>

<div class="um-nexus-wrapper" style="margin-top: 0;">
    <?php if (empty($forum_structure)): ?>
        <div class="admin-box" style="text-align: center; padding: 50px;">
            <p style="color: #666; font-style: italic;">The forums are currently closed.</p>
        </div>
    <?php else: ?>
        <?php foreach ($forum_structure as $cat): ?>
            <div style="margin-bottom: 40px;">
                <h3 style="font-family: 'Cinzel'; color: var(--glow-gold); border-bottom: 1px solid rgba(197,160,89,0.2); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px; font-size: 1.1em;">
                    <?php echo h($cat['info']['title'] ?? 'Unknown Category'); ?>
                    <?php if ($userPriv < ($cat['info']['min_priv_post'] ?? 0)): ?>
                        <span style="font-size: 0.5em; color: #444; vertical-align: middle; margin-left: 10px; letter-spacing: 1px;">
                            <i class="fas fa-eye"></i> READ ONLY
                        </span>
                    <?php endif; ?>
                </h3>

                <div class="spike-board-grid" style="display: grid; gap: 10px;">
                    <?php if (empty($cat['boards'])): ?>
                         <p style="color: #444; font-size: 0.8em; padding-left: 10px;"><i>No boards found.</i></p>
                    <?php else: ?>
                        <?php foreach ($cat['boards'] as $board): 
                            $required_post_priv = (isset($board['min_priv_post']) && $board['min_priv_post'] > 0) 
                                                ? (int)$board['min_priv_post'] 
                                                : (int)($cat['info']['min_priv_post'] ?? 0);

                            $display_threads = isset($board['thread_count']) ? (int)$board['thread_count'] : 0;
                            $display_posts   = isset($board['post_count'])   ? (int)$board['post_count']   : 0;
                            $can_post = ($userPriv >= $required_post_priv);
                        ?>
                            <div class="quick-card" onclick="window.location.href='?p=viewboard&id=<?php echo $board['id']; ?>'" 
                                 style="display: flex; align-items: center; justify-content: space-between; text-align: left; padding: 15px 25px; cursor: pointer; background: rgba(0,0,0,0.3); border: 1px solid #111; transition: 0.3s;">
                                
                                <div style="display: flex; align-items: center; gap: 20px; flex: 1;">
                                    <div style="color: <?php echo $can_post ? 'var(--glow-blue)' : '#333'; ?>; font-size: 1.5em; width: 40px; text-align: center;">
                                        <i class="fas <?php echo $can_post ? 'fa-scroll' : 'fa-book-reader'; ?>"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; color: #eee; font-size: 1.05em;"><?php echo h($board['title'] ?? 'Untitled Board'); ?></h4>
                                        <p style="margin: 3px 0 0 0; color: #666; font-size: 0.85em; font-style: italic;">
                                            <?php echo h($board['description'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>

                                <div style="width: 150px; text-align: center; border-left: 1px solid #111; border-right: 1px solid #111; padding: 0 15px;">
                                    <div style="font-size: 0.9em; color: var(--glow-gold);"><?php echo $display_threads; ?> <span style="font-size: 0.7em; color: #444;">THREADS</span></div>
                                    <div style="font-size: 0.9em; color: #888;"><?php echo $display_posts; ?> <span style="font-size: 0.7em; color: #444;">POSTS</span></div>
                                </div>

                                <div style="text-align: right; min-width: 180px; padding-left: 15px;">
                                    <?php if (!empty($board['last_post_date'])): ?>
                                        <span style="display: block; font-size: 0.7em; color: #444; text-transform: uppercase; letter-spacing: 1px;">Latest Post</span>
                                        <span style="color: #bbb; font-size: 0.8em;"><?php echo date("d.m.Y - H:i", strtotime($board['last_post_date'])); ?></span>
                                        <span style="display: block; color: var(--glow-blue); font-size: 0.75em; font-weight: bold;">
                                            by <?php echo h($board['last_post_user'] ?? 'Anonymous'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #333; font-size: 0.75em; font-style: italic;">Empty</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="border-top: 1px dashed rgba(255,255,255,0.05); padding: 12px 5px; margin-top: 20px; font-size: 10px; color: #444;">
        <strong style="color: #666; text-transform: uppercase; letter-spacing: 1px;">Currently online:</strong> 
        <?php if (empty($online_users)): ?>
            None
        <?php else: ?>
            <?php 
            $user_display = [];
            foreach ($online_users as $ou) {
                $color = '#888';
                if ($ou['priv_level'] >= 5) $color = '#ff4444';
                elseif ($ou['priv_level'] >= 4) $color = 'var(--glow-gold)';
                
                $user_display[] = '<span style="color: '.$color.';">' . h($ou['username']) . '</span>';
            }
            echo implode(', ', $user_display);
            ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .spike-board-grid .quick-card:hover {
        border-left: 4px solid var(--glow-blue) !important;
        padding-left: 21px !important;
        background: rgba(255,255,255,0.05) !important;
        border-color: var(--glow-blue);
    }
</style>