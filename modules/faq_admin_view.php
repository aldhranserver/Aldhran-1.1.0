<?php
/**
 * FAQ ADMIN MANAGER
 * Version: 1.0.0 - Standalone (No Forum)
 */

// Sicherheit: Admin-Check
if (!isset($_SESSION['user_id']) || (int)$_SESSION['priv_level'] < 4) {
    echo "<div class='admin-box'>Access Denied. Insufficient Privileges.</div>";
    return;
}

$message = "";

// --- 1. ACTION HANDLING ---

// Speichern (Neu oder Update)
if (isset($_POST['save_faq'])) {
    $id = (int)$_POST['id'];
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $ques = mysqli_real_escape_string($conn, $_POST['question']);
    $ans = mysqli_real_escape_string($conn, $_POST['answer']);
    $sort = (int)$_POST['sort_order'];

    if ($id > 0) {
        $conn->query("UPDATE faq SET category='$cat', question='$ques', answer='$ans', sort_order=$sort WHERE id=$id");
        $message = "Entry updated successfully.";
    } else {
        $conn->query("INSERT INTO faq (category, question, answer, sort_order) VALUES ('$cat', '$ques', '$ans', $sort)");
        $message = "New FAQ created successfully.";
    }
}

// Löschen
if (isset($_GET['del'])) {
    $del_id = (int)$_GET['del'];
    $conn->query("DELETE FROM faq WHERE id = $del_id");
    $message = "Entry deleted.";
}

// --- 2. EDIT MODE DETECTION ---
$edit_data = ['id' => 0, 'category' => '', 'question' => '', 'answer' => '', 'sort_order' => 0];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM faq WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

// Alle FAQs für die Liste laden
$all_faqs = $conn->query("SELECT * FROM faq ORDER BY category, sort_order ASC");
?>

<div class="admin-container">
    <h2 style="font-family: 'Cinzel'; color: var(--gold); margin-bottom: 20px;">
        <i class="fas fa-question-circle"></i> FAQ Manager
    </h2>
    
    <?php if ($message): ?>
        <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; padding: 15px; margin-bottom: 25px; color: #2ecc71; font-size: 0.9em; border-radius: 4px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="admin-box" style="margin-bottom: 40px; border-left: 3px solid var(--gold);">
        <h3 style="font-size: 0.8em; text-transform: uppercase; margin-bottom: 20px; color: var(--gold);">
            <?php echo ($edit_data['id'] > 0) ? "Edit FAQ Entry #".$edit_data['id'] : "Create New FAQ Scroll"; ?>
        </h3>
        
        <form method="POST" action="index.php?p=faq_admin">
            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <label style="display:block; font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px;">Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($edit_data['category']); ?>" required 
                           style="width:100%; background:#050505; border:1px solid #222; color:#ccc; padding:10px;">
                </div>
                <div>
                    <label style="display:block; font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px;">Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_data['sort_order']; ?>" 
                           style="width:100%; background:#050505; border:1px solid #222; color:#ccc; padding:10px;">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px;">Question</label>
                <input type="text" name="question" value="<?php echo htmlspecialchars($edit_data['question']); ?>" required 
                       style="width:100%; background:#050505; border:1px solid #222; color:#ccc; padding:10px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; font-size: 0.7em; color: #555; text-transform: uppercase; margin-bottom: 5px;">Answer</label>
                <textarea name="answer" rows="6" required 
                          style="width:100%; background:#050505; border:1px solid #222; color:#ccc; padding:10px; resize:vertical; font-family: sans-serif;"><?php echo htmlspecialchars($edit_data['answer']); ?></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <button type="submit" name="save_faq" class="admin-btn" style="background:var(--gold-dark); color:#000; border:none; padding:12px 25px; cursor:pointer; font-weight:bold; text-transform:uppercase; font-size:0.8em;">
                    <i class="fas fa-save"></i> Save Scroll
                </button>
                <?php if ($edit_data['id'] > 0): ?>
                    <a href="?p=faq_admin" style="color:#555; text-decoration:none; font-size:0.8em; text-transform:uppercase; letter-spacing:1px;">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="admin-box">
        <h3 style="font-size: 0.8em; text-transform: uppercase; margin-bottom: 20px; color: #444;">Existing Library Entries</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid #111; color:#555; font-size:0.7em; text-transform:uppercase;">
                    <th style="padding:10px; width:150px;">Category</th>
                    <th>Question</th>
                    <th style="text-align:right; padding-right:10px; width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($all_faqs): while ($row = $all_faqs->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #0a0a0a; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:15px 10px; color:#555; font-size:0.8em;"><?php echo htmlspecialchars($row['category']); ?></td>
                    <td style="color:#eee; font-size:0.9em;"><?php echo htmlspecialchars($row['question']); ?></td>
                    <td style="text-align:right; padding-right:10px;">
                        <a href="?p=faq_admin&edit=<?php echo $row['id']; ?>" style="color:var(--gold); text-decoration:none; margin-right:15px;" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?p=faq_admin&del=<?php echo $row['id']; ?>" onclick="return confirm('Do you want to delete this entry?')" style="color:#ff4444; text-decoration:none;" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>