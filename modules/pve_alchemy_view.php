<?php
/**
 * ALDHRAN ROYAL ALCHEMY - Enterprise Edition
 * Version: 6.0.0 - SECURITY: PDO Core & Atomic Transactions
 */
if (!defined('IN_CMS')) exit;

$status_msg = "";
$user_chars = [];

// GUID (String) sicher via PDO behandeln
$selected_char_id = $_GET['char_id'] ?? '';

$total_balance = 0;
$current_char_lvl = 0;
$list_id = null;

/* ===============================
   HELPER: Kupfer umrechnen
=================================*/
function totalCopper($c) {
    return ((int)($c['Platinum'] ?? 0) * 10000000) + 
           ((int)($c['Gold'] ?? 0) * 10000) + 
           ((int)($c['Silver'] ?? 0) * 100) + 
           ((int)($c['Copper'] ?? 0));
}

/* ===============================
   PURCHASE LOGIC
=================================*/
if (isset($_POST['buy_potion_id'], $_SESSION['user_id'])) {
    // Enterprise V2: CSRF Validation
    checkToken($_POST['csrf_token'] ?? '');

    $item_id = $_POST['buy_potion_id'];
    $char_id = $_POST['char_id'];
    $cms_user_id = (int)$_SESSION['user_id'];
    $db_user = $_SESSION['username'] ?? '';

    // Daten via PDO laden
    $stmt_char = $db->prepare("SELECT * FROM dolcharacters WHERE DOLCharacters_ID = ? AND AccountName = ? LIMIT 1");
    $stmt_char->execute([$char_id, $db_user]);
    $char = $stmt_char->fetch();

    $stmt_item = $db->prepare("SELECT * FROM itemtemplate WHERE Id_nb = ? OR ItemTemplate_ID = ? LIMIT 1");
    $stmt_item->execute([$item_id, $item_id]);
    $item = $stmt_item->fetch();

    if ($item && $char) {
        $price = (int)$item['Price'];
        $current_total = totalCopper($char);

        if ((int)$char['Level'] >= (int)$item['Level'] && $current_total >= $price) {
            
            try {
                $db->beginTransaction();

                $new_total = $current_total - $price;
                $p = floor($new_total / 10000000); $rem = $new_total % 10000000;
                $g = floor($rem / 10000); $rem %= 10000;
                $s = floor($rem / 100); $c = $rem % 100;

                // 1. Gold abziehen
                $upd = $db->prepare("UPDATE dolcharacters SET Platinum = ?, Gold = ?, Silver = ?, Copper = ? WHERE DOLCharacters_ID = ?");
                $upd->execute([$p, $g, $s, $c, $char_id]);

                // 2. Poller-Eintrag schreiben
                $template_id = $item['Id_nb'] ?? $item['ItemTemplate_ID'];
                $ins_order = $db->prepare("INSERT INTO webshop_orders (player_name, item_template_id, count, delivered) VALUES (?, ?, 1, 0)");
                $ins_order->execute([$char['Name'], $template_id]);

                // 3. Log für den Waschsalon
                aldhran_log("SHOP_PURCHASE", "Bought potion: {$item['Name']} for {$char['Name']}", $cms_user_id);

                $db->commit();
                header("Location: index.php?p=pve_alchemy&char_id=$char_id&msg=success");
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Alchemy Purchase Error: " . $e->getMessage());
                $status_msg = "Die Transaktion wurde abgebrochen. Kein Gold abgezogen.";
            }
        } else {
            $status_msg = "Voraussetzungen nicht erfüllt (Level/Gold).";
        }
    }
}

/* ===============================
   CHARACTERS & REALM DETECTION
=================================*/
if (isset($_SESSION['user_id'])) {
    $db_user = $_SESSION['username'] ?? '';

    $stmt_chars = $db->prepare("SELECT * FROM dolcharacters WHERE AccountName = ? ORDER BY Level DESC");
    $stmt_chars->execute([$db_user]);
    $results = $stmt_chars->fetchAll();

    foreach ($results as $row) {
        $row['total_money'] = totalCopper($row);
        $user_chars[] = $row;

        if (trim($selected_char_id) === trim($row['DOLCharacters_ID'])) {
            $total_balance = $row['total_money'];
            $current_char_lvl = (int)$row['Level'];
            if ($row['Realm'] == 3) $list_id = 'Alchemy_Hib';
            elseif ($row['Realm'] == 2) $list_id = 'Alchemy_Mid';
            else $list_id = 'Alchemy_Alb';
        }
    }
}

/* ===============================
   POTIONS LADEN
=================================*/
$potions = [];
if ($list_id) {
    $stmt_pot = $db->prepare("
        SELECT t.* FROM merchantitem m 
        JOIN itemtemplate t ON (m.ItemTemplateID=t.Id_nb OR m.ItemTemplateID=t.ItemTemplate_ID) 
        WHERE m.ItemListID = ? 
        ORDER BY t.Level ASC
    ");
    $stmt_pot->execute([$list_id]);
    $potions = $stmt_pot->fetchAll();
}
?>

<div style="max-width:1000px;margin:0 auto;padding:20px;color:#eee;background:#111;border:1px solid #333;border-radius:8px;font-family:sans-serif;">
    <h3 style="text-align:center;color:#d4af37;letter-spacing:2px;margin-bottom:20px; font-family: 'Cinzel', serif;">ALDHRAN ROYAL ALCHEMY</h3>

    <?php if($status_msg): ?> 
        <div style="color:#ff4444;text-align:center;padding:10px;background:rgba(255,0,0,0.1);border-radius:4px;margin-bottom:15px;"><?=h($status_msg)?></div> 
    <?php endif; ?>
    
    <?php if(($_GET['msg']??'')=="success"): ?> 
        <div style="color:#00ff00;text-align:center;padding:10px;background:rgba(0,255,0,0.1);border-radius:4px;margin-bottom:15px;">Potion has been purchased successfully! Item will be spawned in your inventory or vault.</div> 
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="p" value="pve_alchemy">
        <label style="display:block;margin-bottom:5px;font-size:0.8em;color:#888;">CHOOSE CHARACTER:</label>
        <select name="char_id" onchange="this.form.submit()" style="width:100%;padding:12px;background:#222;color:#d4af37;border:1px solid #444;border-radius:4px;cursor:pointer;font-family: 'Cinzel', serif;">
            <option value="">-- Select Character --</option>
            <?php foreach($user_chars as $c): ?>
                <option value="<?=$c['DOLCharacters_ID']?>" <?=$selected_char_id==$c['DOLCharacters_ID']?'selected':''?>>
                    <?=h($c['Name'])?> (Lvl <?=$c['Level']?>) - <?=number_format($c['total_money']/10000,2)?> Gold
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;margin-top:25px;">
        <?php if ($selected_char_id && count($potions) > 0): ?>
            <?php foreach($potions as $p): 
                $can_buy = ($current_char_lvl >= (int)$p['Level'] && $total_balance >= (int)$p['Price']);
                $p_id = $p['Id_nb'] ?? $p['ItemTemplate_ID']; ?>
                
                <div style="background:#1a1a1a;padding:20px;border:1px solid #333;text-align:center;border-radius:4px; border-top: 2px solid #d4af37;">
                    <h4 style="margin:0 0 10px 0;color:#fff;font-family: 'Cinzel', serif;"><?=h($p['Name'])?></h4>
                    <p style="font-size:0.85em;color:#aaa;margin-bottom:5px;">Req: Lvl <?=$p['Level']?></p>
                    <p style="color:#d4af37;font-weight:bold;font-size:1.2em;margin-bottom:15px;"><?=number_format($p['Price']/10000,2)?> Gold</p>
                    
                    <form method="POST" action="index.php?p=pve_alchemy&char_id=<?=$selected_char_id?>">
                        <input type="hidden" name="csrf_token" value="<?=generateToken()?>">
                        <input type="hidden" name="buy_potion_id" value="<?=$p_id?>">
                        <input type="hidden" name="char_id" value="<?=$selected_char_id?>">
                        <button <?=$can_buy?'':'disabled'?> style="width:100%;padding:12px;cursor:<?=$can_buy?'pointer':'not-allowed'?>;background:<?=$can_buy?'#d4af37':'#222'?>;border:none;color:<?=$can_buy?'#000':'#666'?>;font-weight:bold;border-radius:4px;text-transform:uppercase;letter-spacing:1px;">
                            <?=$can_buy?'Purchase Potion':'Level/Gold Missing'?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php elseif($selected_char_id): ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">There are no potions for this realm.</div>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px; border: 1px dashed #333;">Choose a character above to enter the Alchemist's Chambers.</div>
        <?php endif; ?>
    </div>
</div>