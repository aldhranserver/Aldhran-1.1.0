<?php if (!defined('IN_CMS')) { exit; } ?>

<div class="home-forum-widget" style="margin-bottom: 40px;">
    <h3 style="font-family: 'Cinzel'; color: var(--glow-gold); border-bottom: 1px solid rgba(197,160,89,0.2); padding-bottom: 10px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px;">
        <i class="fas fa-scroll" style="margin-right: 10px; font-size: 0.8em;"></i> Latest Posts
    </h3>

    <?php if (!empty($latest_threads)): ?>
        <div style="display: grid; gap: 10px;">
            <?php foreach ($latest_threads as $lt): 
                // Realm-Farbe für den Balken ermitteln (deine user_view.php Logik)
                $name_esc = mysqli_real_escape_string($conn, $lt['username']);
                $r_res = $conn->query("SELECT DISTINCT Realm FROM dolcharacters WHERE AccountName = '$name_esc' LIMIT 1");
                $r_data = $r_res->fetch_assoc();
                $r_color = '#333'; // Default
                if($r_data) {
                    if($r_data['Realm'] == 1) $r_color = '#F52727';
                    if($r_data['Realm'] == 2) $r_color = '#275BF5';
                    if($r_data['Realm'] == 3) $r_color = '#27F565';
                }
            ?>
                <div class="quick-card" onclick="window.location.href='?p=viewthread&id=<?php echo $lt['id']; ?>'" 
                     style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; border-left: 3px solid <?php echo $r_color; ?>; cursor: pointer;">
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 35px; height: 35px; border: 1px solid #222; overflow: hidden; background: #000;">
                            <?php if(!empty($lt['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($lt['avatar_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 33px; color: #111;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="color: #eee; font-weight: bold; font-size: 0.95em;"><?php echo htmlspecialchars($lt['title']); ?></div>
                            <div style="font-size: 0.75em; color: #555;">
                                by <span style="color: var(--glow-blue);"><?php echo htmlspecialchars($lt['username']); ?></span> in <?php echo htmlspecialchars($lt['board_title']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: right; font-size: 0.75em; color: #444; font-style: italic;">
                        <?php echo date("d.m. H:i", strtotime($lt['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 15px; text-align: right;">
            <a href="?p=spike" style="color: var(--glow-gold); text-decoration: none; font-size: 0.8em; text-transform: uppercase; letter-spacing: 1px;">
                Enter the Forum <i class="fas fa-arrow-right" style="font-size: 0.8em; margin-left: 5px;"></i>
            </a>
        </div>
    <?php else: ?>
        <p style="color: #444; font-style: italic; text-align: center; padding: 20px;">There aren't any posts yet.</p>
    <?php endif; ?>
</div>