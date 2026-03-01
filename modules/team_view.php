<?php
/**
 * TEAM VIEW - Aldhran Enterprise
 * Version: 2.0.0 - SECURITY: PDO Migration & XSS Protection
 */
if (!defined('IN_CMS')) { exit; } 

// Wir nutzen das globale PDO-Objekt $db
global $db;
?>

<div class="team-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
    <?php 
    // Team-Abfrage via PDO
    $stmt = $db->query("SELECT * FROM users WHERE priv_level >= 3 ORDER BY priv_level DESC, username ASC");
    $team_members = $stmt->fetchAll();
    
    if ($team_members):
        foreach ($team_members as $m): 
            // Rollen-Logik (Enterprise-Standard nutzt h() für Sicherheit)
            $role_name = !empty($m['user_title']) ? $m['user_title'] : (function_exists('getRoleName') ? getRoleName($m['priv_level']) : 'Staff');
            
            // Umrandungsfarbe basierend auf Rang
            $border_color = ($m['priv_level'] >= 4) ? '#ff4444' : '#d4af37';
    ?>
    <div class="admin-box" style="text-align:center; border-top: 3px solid <?php echo $border_color; ?>; padding: 25px; background: rgba(0,0,0,0.3); border-radius: 4px; transition: 0.3s;">
        
        <div style="margin-bottom:15px; position:relative; display:inline-block;">
            <?php if(!empty($m['avatar_url']) && file_exists($m['avatar_url'])): ?>
                <img src="<?php echo h($m['avatar_url']); ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:2px solid <?php echo $border_color; ?>;">
            <?php else: ?>
                <div style="width:100px; height:100px; border-radius:50%; background: #0a0a0a; border: 2px solid #222; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield" style="font-size: 40px; color: #1a1a1a;"></i>
                </div>
            <?php endif; ?>
        </div>

        <h2 style="font-family:'Cinzel', serif; margin:0; color: #fff; letter-spacing: 1px; font-size: 1.2em;">
            <?php echo h($m['username'] ?? 'Unknown'); ?>
        </h2>
        
        <div style="color:<?php echo $border_color; ?>; margin: 5px 0 10px; font-style:italic; font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px;">
            <?php echo $role_name; // Hier erlauben wir HTML aus getRoleName (z.B. Gold-Farben) ?>
        </div>

        <?php if(!empty($m['languages'])): ?>
            <div style="display:flex; justify-content:center; gap:5px; margin-bottom:15px; flex-wrap: wrap;">
                <?php 
                $langs = explode(',', $m['languages']);
                foreach($langs as $l): 
                    $clean_l = trim($l);
                    if($clean_l == "") continue;
                ?>
                    <span style="border:1px solid rgba(197,160,89,0.3); background: rgba(197,160,89,0.05); padding:2px 8px; border-radius:10px; font-size:10px; color: #d4af37; text-transform: uppercase;">
                        <?php echo h($clean_l); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php 
        endforeach; 
    endif;
    ?>
</div>