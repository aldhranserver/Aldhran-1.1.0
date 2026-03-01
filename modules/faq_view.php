<?php
/**
 * ALDHRAN FAQ - Accordion Style
 * Version: 1.0.0 - Standalone (No Forum)
 */

// Korrektur: $conn statt $db verwenden
$faq_res = $conn->query("SELECT * FROM faq ORDER BY category, sort_order ASC");
$faqs = [];

if ($faq_res) {
    while ($row = $faq_res->fetch_assoc()) {
        $faqs[$row['category']][] = $row;
    }
}
?>

<div class="admin-container">
    <div class="admin-box" style="padding: 40px;">
        
        <h1 style="font-family: 'Cinzel'; color: var(--gold); margin-bottom: 10px;">
            <?php echo htmlspecialchars($page_title ?? 'Frequently Asked Questions'); ?>
        </h1>
        
        <div style="color: #666; font-size: 0.9em; margin-bottom: 40px;">
            <?php 
                // Zeigt Architect-Content oder Standardtext
                echo ($data['content'] ?? 'Find answers to common questions about the realm of Aldhran.'); 
            ?>
        </div>

        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $category => $items): ?>
                <h3 style="text-transform: uppercase; letter-spacing: 2px; color: #444; font-size: 0.8em; margin-top: 30px; border-bottom: 1px solid #111; padding-bottom: 10px;">
                    <?php echo htmlspecialchars($category); ?>
                </h3>

                <?php foreach ($items as $faq): ?>
                    <div class="faq-item" style="margin-bottom: 10px; border: 1px solid #0a0a0a; background: rgba(0,0,0,0.2);">
                        <div class="faq-question" style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;" onclick="toggleFaq(<?php echo $faq['id']; ?>)">
                            <span style="font-weight: bold; color: #ccc;"><?php echo htmlspecialchars($faq['question']); ?></span>
                            <i class="fas fa-chevron-down" id="icon-<?php echo $faq['id']; ?>" style="font-size: 0.8em; color: var(--gold); transition: transform 0.3s;"></i>
                        </div>
                        <div id="answer-<?php echo $faq['id']; ?>" class="faq-answer" style="display: none; padding: 0 20px 20px 20px; color: #888; font-size: 0.95em; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #444; font-style: italic;">The library is currently being written. Please check back later.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFaq(id) {
    const answer = document.getElementById('answer-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (answer.style.display === 'none') {
        answer.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
        answer.parentElement.style.background = 'rgba(197, 160, 89, 0.03)';
    } else {
        answer.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
        answer.parentElement.style.background = 'rgba(0,0,0,0.2)';
    }
}
</script>

<style>
.faq-question:hover {
    background: rgba(255,255,255,0.02);
    color: var(--gold) !important;
}
.faq-item {
    border-radius: 4px;
    overflow: hidden;
}
</style>