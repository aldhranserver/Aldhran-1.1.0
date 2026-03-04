<?php
/**
 * ALDHRAN FAQ - Accordion Style
 * Version: 2.0.0 - SECURITY: PDO Migration & XSS Protection
 */

// Wir nutzen jetzt das globale PDO Objekt $db
$stmt_faq = $db->query("SELECT * FROM faq ORDER BY category, sort_order ASC");
$faqs = [];

// PDO fetch() Loop
while ($row = $stmt_faq->fetch()) {
    $faqs[$row['category']][] = $row;
}
?>

<div class="admin-container">
    <div class="admin-box" style="padding: 40px; border-top: 2px solid var(--gold);">
        
        <h1 style="font-family: 'Cinzel'; color: var(--gold); margin-bottom: 10px; letter-spacing: 3px;">
            <?php echo h($page_title ?? 'Frequently Asked Questions'); ?>
        </h1>
        
        <div style="color: #666; font-size: 0.9em; margin-bottom: 40px; font-style: italic;">
            <?php 
                // Zeigt Architect-Content oder Standardtext
                echo ($data['content'] ?? 'Find answers to common questions about the realm of Aldhran.'); 
            ?>
        </div>

        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $category => $items): ?>
                <h3 style="text-transform: uppercase; letter-spacing: 2px; color: #555; font-size: 0.75em; margin-top: 40px; border-bottom: 1px solid #111; padding-bottom: 10px; font-family: 'Cinzel', serif;">
                    <?php echo h($category); ?>
                </h3>

                <?php foreach ($items as $faq): ?>
                    <div class="faq-item" style="margin-bottom: 10px; border: 1px solid #111; background: rgba(0,0,0,0.3); transition: 0.3s;">
                        <div class="faq-question" style="padding: 18px 25px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;" onclick="toggleFaq(<?php echo (int)$faq['id']; ?>)">
                            <span style="font-weight: bold; color: #bbb; letter-spacing: 0.5px;"><?php echo h($faq['question']); ?></span>
                            <i class="fas fa-chevron-down" id="icon-<?php echo (int)$faq['id']; ?>" style="font-size: 0.8em; color: var(--gold); transition: transform 0.4s ease;"></i>
                        </div>
                        <div id="answer-<?php echo (int)$faq['id']; ?>" class="faq-answer" style="display: none; padding: 0 25px 25px 25px; color: #888; font-size: 0.95em; line-height: 1.7; border-top: 1px solid rgba(212, 175, 55, 0.05);">
                            <?php echo nl2br(h($faq['answer'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; border: 1px dashed #222; color: #444;">
                <i class="fas fa-scroll" style="font-size: 2em; margin-bottom: 15px; opacity: 0.3;"></i><br>
                The library is currently being written. Please check back later.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFaq(id) {
    const answer = document.getElementById('answer-' + id);
    const icon = document.getElementById('icon-' + id);
    const container = answer.parentElement;
    
    if (answer.style.display === 'none') {
        answer.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
        container.style.background = 'rgba(212, 175, 55, 0.05)';
        container.style.borderColor = 'rgba(212, 175, 55, 0.2)';
    } else {
        answer.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
        container.style.background = 'rgba(0,0,0,0.3)';
        container.style.borderColor = '#111';
    }
}
</script>

<style>
.faq-question:hover {
    background: rgba(212, 175, 55, 0.03);
}
.faq-question:hover span {
    color: var(--gold) !important;
}
.faq-item {
    border-radius: 2px;
}
</style>