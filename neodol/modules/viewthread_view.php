<?php 
/**
 * VIEWTHREAD VIEW - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & CSRF Protection
 */
if (!defined('IN_CMS')) { exit; } 

function renderRankStars($bs) {
    $bs = (int)$bs;
    $count = 0;
    if ($bs === 1) $count = 1;
    elseif ($bs === 2) $count = 2;
    elseif ($bs === 3) $count = 5;
    elseif ($bs === 4) $count = 6;
    elseif ($bs >= 5) $count = 7;
    
    $output = '<div style="margin-top:4px; display: flex; align-items: center; justify-content: center; gap: 8px;">';
    if ($count > 0) {
        $output .= '<div style="color:var(--glow-gold); font-size:0.55em; letter-spacing:2px; opacity:0.7;">';
        for($i = 0; $i < $count; $i++) { $output .= '<i class="fas fa-star"></i>'; }
        $output .= '</div>';
    }
    if ($bs >= 4) {
        $output .= '<i class="fas fa-shield-alt" style="color: var(--glow-blue); font-size: 0.8em; filter: drop-shadow(0 0 5px var(--glow-blue));" title="Ordnungsmacht"></i>';
    }
    $output .= '</div>';
    return ($count === 0 && $bs < 4) ? '' : $output;
}

// --- Effektive Post-Berechtigung ---
$effective_min_post = ($thread['board_min_post'] > 0) ? (int)$thread['board_min_post'] : (int)$thread['cat_min_post'];
$can_actually_post = ($myId > 0 && $myStanding < 3 && $myPriv >= $effective_min_post);
?>

<div class="um-nexus-wrapper">
    
    <?php if(isset($_GET['err'])): ?>
        <div style="margin-bottom:20px; padding:15px; border-radius:5px; background:rgba(200,0,0,0.1); border:1px solid var(--error-red); color:#fff; font-size:0.85em;">
            <?php if($_GET['err'] === 'spam_cooldown'): ?>
                <i class="fas fa-clock"></i> Calm down! Wait <strong><?php echo (int)$_GET['wait']; ?>s</strong>.
            <?php elseif($_GET['err'] === 'unauthorized_post'): ?>
                <i class="fas fa-exclamation-triangle"></i> Access denied. Restricted account or low BS level.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="um-internal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h2 class="um-internal-title">
                <?php if($thread['is_sticky']): ?><i class="fas fa-thumbtack" style="color:var(--glow-gold); font-size:0.7em;"></i><?php endif; ?>
                <?php echo h($thread['title']); ?>
                <?php if($thread['is_locked']): ?><i class="fas fa-lock" style="color:#666; font-size:0.7em;"></i><?php endif; ?>
            </h2>
            <small style="color:#444;">BOARD: <?php echo h($thread['board_title']); ?></small>
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if ($myPriv >= 3): ?>
                <form method="POST" action="index.php?p=viewthread&id=<?php echo (int)$thread['id']; ?>" style="margin:0; display:flex; gap:5px;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                    <input type="hidden" name="thread_id" value="<?php echo (int)$thread['id']; ?>">
                    
                    <button type="submit" name="mod_action" value="toggle_lock" class="btn-nexus-edit" title="Lock/Unlock">
                        <i class="fas <?php echo $thread['is_locked'] ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                    </button>
                    
                    <button type="submit" name="mod_action" value="toggle_sticky" class="btn-nexus-edit" title="Sticky/Unsticky">
                        <i class="fas fa-thumbtack"></i>
                    </button>
                    
                    <?php if ($myPriv >= 4): ?>
                        <button type="submit" name="mod_action" value="delete_thread" class="btn-nexus-edit" style="color:var(--error-red); border-color:var(--error-red);" onclick="return confirm('DELETE ENTIRE THREAD AND ALL POSTS?')">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <a href="?p=viewboard&id=<?php echo (int)$thread['board_id']; ?>" 
               style="text-decoration: none; background: #111; color: #ccc; border: 1px solid #333; padding: 8px 18px; font-size: 0.75em; font-weight: bold; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 10px; transition: 0.2s;">
                <i class="fas fa-chevron-left" style="font-size: 0.8em;"></i> BACK
            </a>
        </div>
    </div>

    <?php 
    // FIX: Wir iterieren nun über das PDO-Array aus der Logic
    if(!empty($posts)):
        foreach($posts as $p): 
            $is_author = ($myId > 0 && $myId == $p['author_id']);
            $can_edit = ($can_actually_post && $is_author) || $myPriv >= 3;
            $isAdmin = (isset($p['priv_level']) && (int)$p['priv_level'] >= 4);
            $adminBorderStyle = $isAdmin ? 'border-left: 4px solid #ff0000 !important;' : '';
            $adminNameStyle = $isAdmin ? 'color: #ff0000 !important; font-weight: bold !important;' : 'color: var(--glow-blue); font-weight: bold;';
    ?>
        <div class="admin-box" style="padding:0; margin-bottom:20px; display:flex; min-height:200px; background:rgba(5,5,5,0.95); <?php echo $adminBorderStyle; ?>">
            <div style="width:180px; padding:20px; background:rgba(255,255,255,0.02); text-align:center; border-right:1px solid #111;">
                <?php if(!empty($p['avatar_url'])): ?>
                    <img src="<?php echo h($p['avatar_url']); ?>" style="width:80px; height:80px; border:1px solid #333; object-fit:cover; margin-bottom:10px;">
                <?php endif; ?>
                <div style="<?php echo $adminNameStyle; ?>"><?php echo h($p['username'] ?? 'Ghost'); ?></div>
                <div style="color:var(--glow-gold); font-size:0.75em;"><?php echo h($p['user_title'] ?? 'Soul'); ?></div>
                
                <?php echo renderRankStars($p['priv_level'] ?? 0); ?>
                <div style="margin-top: 8px; font-size: 0.7em; color: #555; text-transform: uppercase; letter-spacing: 1px;">
                    Posts: <span style="color: var(--glow-blue); font-weight: bold;"><?php echo (int)($p['forum_posts'] ?? 0); ?></span>
                </div>
            </div>

            <div style="flex:1; padding:25px; position:relative; display: flex; flex-direction: column;">
                <div style="font-size:0.7em; color:#444; position:absolute; top:10px; right:15px; display:flex; gap:15px; align-items:center;">
                    <?php if ($myPriv >= 3): ?>
                        <form method="POST" action="index.php?p=viewthread&id=<?php echo (int)$thread['id']; ?>" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                            <input type="hidden" name="mod_action" value="delete_post">
                            <input type="hidden" name="post_id" value="<?php echo (int)$p['id']; ?>">
                            <button type="submit" style="background:none; border:none; color:var(--error-red); cursor:pointer;" onclick="return confirm('Kill this specific post?')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if($can_edit): ?>
                        <a href="?p=editpost&id=<?php echo (int)$p['id']; ?>" style="color:var(--glow-blue); text-decoration:none; font-weight:bold;"><i class="fas fa-edit"></i> EDIT</a>
                    <?php endif; ?>

                    <?php if($can_actually_post && !$thread['is_locked']): ?>
                        <a href="#quick-reply-box" onclick="quotePost('<?php echo addslashes($p['username'] ?? 'Ghost'); ?>', 'post-content-<?php echo (int)$p['id']; ?>')" style="color:var(--glow-gold); text-decoration:none;"><i class="fas fa-quote-left"></i> QUOTE</a>
                    <?php endif; ?>
                    <span><?php echo date("d.m.Y - H:i", strtotime($p['created_at'])); ?></span>
                </div>

                <div id="post-content-<?php echo (int)$p['id']; ?>" style="color:#ccc; line-height:1.7; margin-top:15px; flex-grow: 1;">
                     <?php echo parseBBCode($p['content']); ?>
                </div>

                <?php if(!empty($p['forum_signature'])): ?>
                    <div class="user-signature" style="margin-top: 20px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05); color: #666; font-size: 0.85em;">
                        <?php echo parseBBCode($p['forum_signature']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; endif; ?>

    <?php if($can_actually_post && !$thread['is_locked']): ?>
        <div id="quick-reply-box" class="admin-box" style="padding:25px;">
            <form method="POST" action="index.php?p=viewthread&id=<?php echo (int)$thread['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                <textarea name="reply_content" required rows="6" style="width:100%; padding:15px; background:#000; border:1px solid #222; color:#ccc;"></textarea>
                <button type="submit" name="submit_reply" class="btn-add-user" style="margin-top:15px;">POST REPLY</button>
            </form>
        </div>
    <?php elseif($myId > 0 && !$can_actually_post): ?>
        <div class="admin-box" style="padding:20px; border-color: #444; opacity: 0.6; text-align: center; border-left: 3px solid var(--error-red);">
            <i class="fas fa-lock" style="color: #666;"></i> 
            <span style="color: #666; font-size: 0.9em; margin-left: 10px;">This sector is <strong>READ ONLY</strong> for your current clearance level.</span>
        </div>
    <?php endif; ?>
</div>

<script>
function quotePost(author, contentId) {
    const content = document.getElementById(contentId).innerText.trim();
    const replyArea = document.querySelector('textarea[name="reply_content"]');
    if(!replyArea) return;
    replyArea.value = "[quote=" + author + "]" + content + "[/quote]\n\n" + replyArea.value;
    replyArea.focus();
}
</script>