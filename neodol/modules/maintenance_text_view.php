<div class="um-editor-container" style="animation: fadeIn 0.3s ease; background: rgba(0,0,0,0.4); padding: 30px; border-radius: 15px; border: 1px solid var(--glow-gold);">
<form method="POST">
        <div class="quick-card" style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px; border-left: 3px solid var(--glow-gold);">
            <label style="display: block; margin-bottom: 10px; color: #aaa; font-size: 0.9em;">HTML allowed</label>
            <textarea name="maint_message" class="um-input-search-glow" 
                      style="width: 100%; height: 150px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid #333; padding: 15px; font-family: 'Open Sans', sans-serif; resize: vertical;"><?php echo htmlspecialchars($current_maint_text); ?></textarea>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="color: #555; text-decoration: none; font-weight: bold; font-size: 0.8em;">← Cancel</a>
            <button type="submit" name="save_maint_text" class="btn-nexus-edit" style="border-color: var(--glow-gold); color: var(--glow-gold); padding: 10px 30px;">
                Save
            </button>
        </div>
    </form>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
    <div style="margin-top: 20px; color: #2ecc71; text-align: center; font-size: 0.9em; animation: fadeIn 1s;">
        <i class="fas fa-check-circle"></i> The maintenance message has been successfully changed
    </div>
<?php endif; ?>