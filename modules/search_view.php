<?php if (!defined('IN_CMS')) exit; ?>

<div class="um-nexus-wrapper">
    <div class="um-internal-header">
        <h2 class="um-internal-title"><i class="fas fa-search"></i> Search Results: "<?php echo htmlspecialchars($query); ?>"</h2>
    </div>

    <?php if (strlen($query) < 3): ?>
        <p class="admin-box">Please enter at least 3 characters to scry the chronicles.</p>
    <?php else: ?>
        
        <?php if (!empty($results['users'])): ?>
            <h3 style="color:var(--glow-gold); font-family:'Cinzel'; margin-top:20px;">Found Players</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach($results['users'] as $u): ?>
                    <a href="?p=user&id=<?php echo $u['id']; ?>" class="quick-card" style="display:flex; align-items:center; gap:15px; padding:10px;">
                        <img src="<?php echo !empty($u['avatar_url']) ? $u['avatar_url'] : 'assets/img/default_av.png'; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <span><?php echo htmlspecialchars($u['username']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($results['threads'])): ?>
            <h3 style="color:var(--glow-gold); font-family:'Cinzel'; margin-top:30px;">Chronicles (Threads)</h3>
            <div class="admin-box" style="padding:0;">
                <?php foreach($results['threads'] as $t): ?>
                    <a href="?p=viewthread&id=<?php echo $t['id']; ?>" class="result-item" style="display:block; padding:15px; border-bottom:1px solid #111; color:var(--glow-blue); text-decoration:none;">
                        <i class="fas fa-scroll"></i> <?php echo htmlspecialchars($t['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($results['posts'])): ?>
            <h3 style="color:var(--glow-gold); font-family:'Cinzel'; margin-top:30px;">Fragments (Posts)</h3>
            <?php foreach($results['posts'] as $p): ?>
                <div class="admin-box" style="margin-bottom:10px;">
                    <a href="?p=viewthread&id=<?php echo $p['thread_id']; ?>" style="color:var(--glow-blue); font-weight:bold; display:block; margin-bottom:5px;">
                        In: <?php echo htmlspecialchars($p['title']); ?>
                    </a>
                    <div style="font-size:0.85em; color:#666; font-style:italic;">
                        "...<?php echo mb_strimwidth(strip_tags(parseBBCode($p['content'])), 0, 150, "..."); ?>"
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($results['users']) && empty($results['threads']) && empty($results['posts'])): ?>
            <p class="admin-box">The spirits find nothing matching your query.</p>
        <?php endif; ?>

    <?php endif; ?>
</div>