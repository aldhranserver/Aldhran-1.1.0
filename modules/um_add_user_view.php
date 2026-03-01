<div class="um-editor-container" style="animation: fadeIn 0.3s ease;">
    <h2 class="um-internal-title" style="color: var(--glow-gold);">Create New User Entry</h2>
    
    <form action="modules/um_sync_worker.php" method="POST">
        <input type="hidden" name="um_action" value="create_user">
        <input type="hidden" name="can_edit" value="1">

        <div class="um-quick-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="quick-card" style="text-align: left; cursor: default;">
                <label style="font-size: 0.8em; color: #888; text-transform: uppercase;">Username</label>
                <input type="text" name="u_name" class="um-input-search-glow" required style="width: 100%; margin-bottom:15px;" placeholder="Account Name">
                
                <label style="font-size: 0.8em; color: #888; text-transform: uppercase;">Email Address</label>
                <input type="email" name="u_email" class="um-input-search-glow" required style="width: 100%;" placeholder="protocol@aldhran.de">
            </div>
            
            <div class="quick-card" style="text-align: left; cursor: default;">
                <label style="font-size: 0.8em; color: #888; text-transform: uppercase;">Initial Password</label>
                <input type="password" name="u_pass" class="um-input-search-glow" required style="width: 100%; margin-bottom:15px;" placeholder="••••••••">
                
                <label style="font-size: 0.8em; color: #888; text-transform: uppercase;">Berechtigungsstufe (BS)</label>
                <select name="u_priv" class="um-input-search-glow" style="width: 100%;">
                    <option value="1">BS: 1 - Player</option>
                    <option value="2">BS: 2 - Councillor</option>
                    <option value="3">BS: 3 - Staff</option>
                    <option value="4">BS: 4 - Admin</option>
                    <option value="5" style="color: var(--glow-gold);">BS: 5 - Super Admin</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" onclick="document.getElementById('nexus-ajax-container').innerHTML=''" 
                    style="background:none; border:none; color:#555; cursor:pointer; font-size: 0.9em;">
                ← Abort Mission
            </button>
            <button type="submit" class="btn-add-user" style="width: 280px; letter-spacing: 1px;">
                EXECUTE CREATION PROTOCOL
            </button>
        </div>
    </form>
</div>