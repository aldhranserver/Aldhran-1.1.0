<?php if (!defined('IN_CMS')) { exit; } ?>

<div class="um-nexus-wrapper">
    <div class="um-internal-header" style="margin-bottom: 30px;">
        <h2 class="um-internal-title" style="font-family: 'Cinzel', serif; letter-spacing: 2px; text-transform: uppercase;">
            Create New Thread in: <?php echo htmlspecialchars($board['title']); ?>
        </h2>
    </div>

    <div class="admin-box" style="border:1px solid rgba(212, 175, 55, 0.1); background: rgba(5,5,5,0.98); padding: 40px; border-top: 3px solid #d4af37;">
        <form method="POST">
            <div style="margin-bottom: 25px;">
                <label style="color: #444; font-size: 0.7em; letter-spacing: 2px; text-transform: uppercase;">Topic Title</label>
                <input type="text" name="thread_title" class="um-input-search-glow" style="width:100%; margin-top:10px; padding: 12px; background: #000; color: #fff; border: 1px solid #1a1a1a; font-size: 0.9em;" required autofocus placeholder="Title...">
            </div>
            
            <div style="background: #fff; border-radius: 2px; padding: 2px; margin-bottom: 25px;">
                <textarea name="thread_content" id="spike_full_editor" required></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="submit" name="submit_thread" 
                   style="background: rgba(212, 175, 55, 0.05); color: #d4af37; border: 1px solid rgba(212, 175, 55, 0.4); padding: 6px 16px; font-size: 0.7em; font-weight: bold; letter-spacing: 1px; cursor: pointer; transition: 0.3s; text-transform: uppercase; font-family: 'Cinzel', serif; display: inline-flex; align-items: center; gap: 8px;"
                   onmouseover="this.style.background='#d4af37'; this.style.color='#000'; this.style.borderColor='#d4af37';"
                   onmouseout="this.style.background='rgba(212, 175, 55, 0.05)'; this.style.color='#d4af37'; this.style.borderColor='rgba(212, 175, 55, 0.4)';"
                   title="Post this thread">
                    <i class="fas fa-plus" style="font-size: 0.8em;"></i> Publish
                </button>

                <a href="?p=viewboard&id=<?php echo $board_id; ?>" 
                   style="text-decoration: none; background: rgba(212, 175, 55, 0.05); color: #d4af37; border: 1px solid rgba(212, 175, 55, 0.4); padding: 6px 16px; font-size: 0.7em; font-weight: bold; letter-spacing: 1px; transition: 0.3s; text-transform: uppercase; font-family: 'Cinzel', serif; display: inline-flex; align-items: center;"
                   onmouseover="this.style.background='#d4af37'; this.style.color='#000'; this.style.borderColor='#d4af37';"
                   onmouseout="this.style.background='rgba(212, 175, 55, 0.05)'; this.style.color='#d4af37'; this.style.borderColor='rgba(212, 175, 55, 0.4)';"
                   title="Discard changes">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
    if (document.getElementById('spike_full_editor')) {
        CKEDITOR.replace('spike_full_editor', {
            height: 450,
            uiColor: '#111111',
            allowedContent: true,
            toolbar: [
                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ] },
                { name: 'links', items: [ 'Link', 'Unlink' ] },
                { name: 'insert', items: [ 'Image', 'Smiley', 'SpecialChar' ] },
                '/',
                { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                { name: 'colors', items: [ 'TextColor', 'BGColor' ] }
            ]
        });
    }
</script>