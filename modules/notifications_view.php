<?php if (!defined('IN_CMS')) { exit; } ?>

<div class="um-nexus-wrapper">
    <div class="um-internal-header">
        <h2 class="um-internal-title">
            <i class="fas fa-envelope-open-text" style="color: var(--glow-gold); margin-right: 15px;"></i> 
            Messenger of Aldhran
        </h2>
        
        <?php if ($unread_count > 0): ?>
            <form method="POST" style="margin:0;">
                <button type="submit" name="mark_all_read" class="btn-nexus-edit" style="font-size: 10px;">
                    <i class="fas fa-check-double"></i> MARK ALL AS READ
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="admin-box" style="padding: 0; background: rgba(5,5,5,0.8);">
        <?php if ($notif_res && $notif_res->num_rows > 0): ?>
            <div class="notif-list">
                <?php while($n = $notif_res->fetch_assoc()): 
                    $is_new = ($n['is_read'] == 0);
                ?>
                    <div class="notif-item" style="display: flex; align-items: center; justify-content: space-between; padding: 20px; border-bottom: 1px solid #111; <?php echo $is_new ? 'background: rgba(197,160,89,0.03); border-left: 3px solid var(--glow-gold);' : 'opacity: 0.6; border-left: 3px solid #222;'; ?>">
                        
                        <div style="display: flex; align-items: center; gap: 20px; cursor: pointer;" onclick="window.location.href='?p=viewthread&id=<?php echo $n['thread_id']; ?>'">
                            <div style="color: <?php echo $is_new ? 'var(--glow-gold)' : '#444'; ?>; font-size: 1.2em;">
                                <i class="fas <?php echo $is_new ? 'fa-comment-dots' : 'fa-comment'; ?>"></i>
                            </div>
                            <div>
                                <div style="color: #eee; font-size: 0.95em;">
                                    <strong style="color: var(--glow-blue);"><?php echo htmlspecialchars($n['username']); ?></strong> 
                                    replied to your post 
                                    <span style="color: var(--glow-gold); font-style: italic;">"<?php echo htmlspecialchars($n['thread_title']); ?>"</span>
                                </div>
                                <div style="color: #444; font-size: 0.75em; margin-top: 5px;">
                                    <i class="far fa-clock"></i> <?php echo date("d.m.Y - H:i", strtotime($n['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <a href="?p=notifications&delete_notif=<?php echo $n['id']; ?>" style="color: #333; transition: 0.3s;" onmouseover="this.style.color='#ff4444'" onmouseout="this.style.color='#333'">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="padding: 60px; text-align: center; color: #444;">
                <i class="fas fa-dove" style="font-size: 3em; display: block; margin-bottom: 20px; opacity: 0.2;"></i>
                <p style="font-style: italic; letter-spacing: 1px;">There are no notifications.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .notif-item:hover {
        background: rgba(255,255,255,0.02) !important;
    }
</style>