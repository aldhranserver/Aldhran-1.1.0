<?php
/**
 * TEAM VIEW - Aldhran Freeshard
 * Version: 1.0.0 - Standalone (No Forum)
 */
?>

<div class="team-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
    <?php 
    // Nutzt nun $conn (CMS Core) statt der alten $db (MyBB)
    $team = $conn->query("SELECT * FROM users WHERE priv_level >= 3 ORDER BY priv_level DESC, username ASC");
    
    if($team):
        while($m = $team->fetch_assoc()): 
            // Fallback für Rollenname (getRoleName kommt aus der db.php)
            $display_title = !empty($m['user_title']) ? $m['user_title'] : (function_exists('getRoleName') ? getRoleName($m['priv_level']) : 'Staff');
            // HTML Tags aus getRoleName entfernen für das title-Attribut oder falls reiner Text gewünscht
            $display_title_clean = strip_tags($display_title);
    ?>
    <div class="admin-box" style="text-align:center; border-top: 3px solid <?php echo ($m['priv_level'] >= 4) ? '#ff4444' : '#d4af37'; ?>; padding: 25px; background: rgba(0,0,0,0.3);">
        
        <?php if(!empty($m['avatar_url']) && file_exists($m['avatar_url'])): ?>
            <img src="<?php echo htmlspecialchars($m['avatar_url']); ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid #d4af37; margin-bottom:15px;">
        <?php else: ?>
            <div style="width:100px; height:100px; border-radius:50%; background: #0a0a0a; border: 2px solid #222; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user-shield" style="font-size: 40px; color: #1a1a1a;"></i>
            </div>
        <?php endif; ?>

        <h2 style="font-family:'Cinzel', serif; margin:0; color: #fff; letter-spacing: 1px; font-size: 1.2em;">
            <?php echo htmlspecialchars($m['username'] ?? 'Unknown'); ?>
        </h2>
        
        <div style="color:#d4af37; margin-bottom:10px; font-style:italic; font-size: 0.9em; letter-spacing: 1px;">
            <?php echo $display_title; // Hier lassen wir HTML zu, falls getRoleName Farben liefert ?>
        </div>

        <?php if(!empty($m['languages'])): ?>
            <div style="display:flex; justify-content:center; gap:5px; margin-bottom:15px;">
                <?php 
                $langs = explode(',', $m['languages']);
                foreach($langs as $l): 
                    if(trim($l) == "") continue;
                ?>
                    <span style="border:1px solid rgba(197,160,89,0.3); background: rgba(197,160,89,0.05); padding:2px 8px; border-radius:10px; font-size:10px; color: #d4af37; text-transform: uppercase;">
                        <?php echo htmlspecialchars(trim($l)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="color:#bbb; font-size:0.9em; line-height: 1.6; min-height: 40px; margin-bottom: 0;">
            <?php 
                echo nl2br(htmlspecialchars($m['description'] ?? '')); 
            ?>
        </p>
    </div>
    <?php 
        endwhile; 
    endif;
    ?>
</div>