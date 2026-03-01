<?php
/**
 * ALDHRAN ROYAL ALCHEMY
 * Version 5.3 - Full GUID Compatibility & Poller Integration
 */
if (!defined('IN_CMS')) exit;

$status_msg = "";
$user_chars = [];

// FIX: char_id als String (GUID) behandeln
$selected_char_id = isset($_GET['char_id']) ? mysqli_real_escape_string($conn, $_GET['char_id']) : '';

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
    $item_id = mysqli_real_escape_string($conn, $_POST['buy_potion_id']);
    $char_id = mysqli_real_escape_string($conn, $_POST['char_id']);
    $cms_user_id = (int)$_SESSION['user_id'];

    $u_data = $conn->query("SELECT username FROM users WHERE id=$cms_user_id LIMIT 1")->fetch_assoc();
    $db_user = mysqli_real_escape_string($conn, $u_data['username'] ?? '');

    $char = $conn->query("SELECT * FROM dolcharacters WHERE DOLCharacters_ID='$char_id' AND AccountName='$db_user' LIMIT 1")->fetch_assoc();
    $item = $conn->query("SELECT * FROM itemtemplate WHERE Id_nb='$item_id' OR ItemTemplate_ID='$item_id' LIMIT 1")->fetch_assoc();

    if ($item && $char) {
        $price = (int)$item['Price'];
        $current_total = totalCopper($char);

        if ((int)$char['Level'] >= (int)$item['Level'] && $current_total >= $price) {
            $new_total = $current_total - $price;
            $p = floor($new_total / 10000000); $rem = $new_total % 10000000;
            $g = floor($rem / 10000); $rem %= 10000;
            $s = floor($rem / 100); $c = $rem % 100;

            if ($conn->query("UPDATE dolcharacters SET Platinum=$p, Gold=$g, Silver=$s, Copper=$c WHERE DOLCharacters_ID='$char_id'")) {
                $template_id = $item['Id_nb'] ?? $item['ItemTemplate_ID'];
                $char_name = mysqli_real_escape_string($conn, $char['Name']);
                
                // Eintrag für den C#-Poller
                $conn->query("INSERT INTO webshop_orders (player_name, item_template_id, count, delivered) 
                              VALUES ('$char_name', '$template_id', 1, 0)");

                header("Location: index.php?p=pve_alchemy&char_id=$char_id&msg=success");
                exit;
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
    $cms_user_id = (int)$_SESSION['user_id'];
    $u_data = $conn->query("SELECT username FROM users WHERE id=$cms_user_id LIMIT 1")->fetch_assoc();
    $db_user = mysqli_real_escape_string($conn, $u_data['username'] ?? '');

    $char_res = $conn->query("SELECT * FROM dolcharacters WHERE AccountName='$db_user' ORDER BY Level DESC");
    while($row = $char_res->fetch_assoc()) {
        $row['total_money'] = totalCopper($row);
        $user_chars[] = $row;

        // GUID Vergleich (String-basiert)
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
$potions = null;
if ($list_id) {
    $potions = $conn->query("
        SELECT t.* FROM merchantitem m 
        JOIN itemtemplate t ON (m.ItemTemplateID=t.Id_nb OR m.ItemTemplateID=t.ItemTemplate_ID) 
        WHERE m.ItemListID='$list_id' 
        ORDER BY t.Level ASC
    ");
}
?>

<div style="max-width:1000px;margin:0 auto;padding:20px;color:#eee;background:#111;border:1px solid #333;border-radius:8px;font-family:sans-serif;">
    <h3 style="text-align:center;color:#d4af37;letter-spacing:2px;margin-bottom:20px;">ALDHRAN POTION SHOP</h3>

    <?php if($status_msg): ?> 
        <div style="color:#ff4444;text-align:center;padding:10px;background:rgba(255,0,0,0.1);border-radius:4px;margin-bottom:15px;"><?=$status_msg?></div> 
    <?php endif; ?>
    
    <?php if(($_GET['msg']??'')=="success"): ?> 
        <div style="color:#00ff00;text-align:center;padding:10px;background:rgba(0,255,0,0.1);border-radius:4px;margin-bottom:15px;">Potion has been purchased successfully! Item will be spawned in your inventory or vault.</div> 
    <?php endif; ?>

    <form method="GET" action="index.php">
        <input type="hidden" name="p" value="pve_alchemy">
        <label style="display:block;margin-bottom:5px;font-size:0.8em;color:#888;">CHARACTER:</label>
        <select name="char_id" onchange="this.form.submit()" style="width:100%;padding:12px;background:#222;color:#d4af37;border:1px solid #444;border-radius:4px;cursor:pointer;">
            <option value="">-- Choose a character --</option>
            <?php foreach($user_chars as $c): ?>
                <option value="<?=$c['DOLCharacters_ID']?>" <?=$selected_char_id==$c['DOLCharacters_ID']?'selected':''?>>
                    <?=$c['Name']?> (Lvl <?=$c['Level']?>) - <?=number_format($c['total_money']/10000,2)?> Gold
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;margin-top:25px;">
        <?php if ($selected_char_id && $potions && $potions->num_rows > 0): ?>
            <?php while($p=$potions->fetch_assoc()): 
                $can_buy = ($current_char_lvl >= (int)$p['Level'] && $total_balance >= (int)$p['Price']);
                $p_id = $p['Id_nb'] ?? $p['ItemTemplate_ID']; ?>
                
                <div style="background:#1a1a1a;padding:20px;border:1px solid #333;text-align:center;border-radius:4px;">
                    <h4 style="margin:0 0 10px 0;color:#fff;"><?=$p['Name']?></h4>
                    <p style="font-size:0.85em;color:#aaa;margin-bottom:5px;">Requirements: Lvl <?=$p['Level']?></p>
                    <p style="color:#d4af37;font-weight:bold;font-size:1.2em;margin-bottom:15px;"><?=number_format($p['Price']/10000,2)?> Gold</p>
                    
                    <form method="POST" action="index.php?p=pve_alchemy&char_id=<?=$selected_char_id?>">
                        <input type="hidden" name="buy_potion_id" value="<?=$p_id?>">
                        <input type="hidden" name="char_id" value="<?=$selected_char_id?>">
                        <button <?=$can_buy?'':'disabled'?> style="width:100%;padding:12px;cursor:<?=$can_buy?'pointer':'not-allowed'?>;background:<?=$can_buy?'#d4af37':'#222'?>;border:none;color:<?=$can_buy?'#000':'#666'?>;font-weight:bold;border-radius:4px;">
                            <?=$can_buy?'BUY NOW':'LEVEL/NOT ENOUGH GOLD'?>
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php elseif($selected_char_id): ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">There are no potions for this realm.</div>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">Choose a character above.</div>
        <?php endif; ?>
    </div>
</div>