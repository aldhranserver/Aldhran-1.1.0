<?php 
/**
 * EDITPOST VIEW - Spike Forum
 * Version: 1.8.1 - CKEditor Integration (AM-Style)
 * Location: /modules/editpost_view.php
 */
if (!defined('IN_CMS')) { exit; } 
?>

<div class="um-nexus-wrapper">
    <div class="um-internal-header" style="margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 15px;">
        <h2 class="um-internal-title" style="color:var(--glow-blue); font-family:'Cinzel';">EDIT POST #<?php echo (int)$post_id; ?></h2>
    </div>

    <div class="admin-box" style="padding:25px; background:rgba(5,5,5,0.95); border:1px solid #111;">
        <form method="POST" action="index.php?p=editpost&id=<?php echo (int)$post_id; ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="color:var(--glow-gold); display:block; margin-bottom:10px; font-size:0.8em; letter-spacing:1px; font-family:'Cinzel';">CONTENT EDITOR</label>
                
                <div class="editor-matrix-bg" style="background: #fff; border-radius: 2px; padding: 2px;">
                    <textarea name="content" id="spike_ckeditor"><?php echo htmlspecialchars($post_data['content']); ?></textarea>
                </div>
            </div>
            
            <div style="margin-top:25px; display:flex; gap:15px; align-items: center;">
                <button type="submit" name="save_edit" class="btn-add-user" style="width:220px; cursor:pointer;">
                    <i class="fas fa-save"></i> SAVE CHANGES
                </button>
                
                <a href="index.php?p=viewthread&id=<?php echo (int)$post_data['thread_id']; ?>" 
                   style="text-decoration:none; color:#555; font-size:0.75em; letter-spacing:1px; font-weight:bold; transition: 0.2s;"
                   onmouseover="this.style.color='#fff';"
                   onmouseout="this.style.color='#555';">
                   CANCEL
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<script>
    // Initialisierung mit der Toolbar-Konfiguration aus deinem Screenshot
    CKEDITOR.replace('spike_ckeditor', {
        height: 450,
        uiColor: '#f5f5f5',
        toolbar: [
            { name: 'document', items: [ 'Source' ] },
            { name: 'clipboard', items: [ 'Undo', 'Redo' ] },
            { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
            { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
            { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
            { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'Iframe' ] },
            '/',
            { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
            { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
            { name: 'tools', items: [ 'Maximize' ] }
        ],
        // Erlaubt alle HTML Tags, damit nichts beim Speichern gefiltert wird
        allowedContent: true 
    });
</script>