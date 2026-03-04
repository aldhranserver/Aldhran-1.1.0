<?php 
/**
 * UM VIEW - NeoDOL Standalone
 * Version: 2.0.0 - NO DOL BRIDGE | STANDALONE UI
 */
if (!isset($can_edit) || !$can_edit) return; 

$is_super_user = (isset($_SESSION['priv_level']) && $_SESSION['priv_level'] >= 5);

// WICHTIG: Token für alle AJAX-Aktionen
$ajax_token = generateToken(); 
?>

<style>
    :root {
        --glow-blue: #00d4ff;
        --glow-gold: #c5a059;
        --bg-dark: rgba(10, 10, 15, 0.98);
        --card-bg: rgba(255, 255, 255, 0.05);
        --error-red: #ff4d4d;
        --warning-orange: #ffa500;
        --border-color: rgba(255, 255, 255, 0.1);
    }
    .um-nexus-wrapper { font-family: 'Segoe UI', sans-serif; color: #e0e0e0; max-width: 1200px; margin: 20px auto; padding: 25px; background: var(--bg-dark); border-radius: 12px; border: 1px solid var(--border-color); }
    .um-internal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 30px; }
    .um-internal-title { margin: 0; font-family: 'Cinzel', serif; color: var(--glow-gold); font-size: 1.6em; text-transform: uppercase; letter-spacing: 2px; }
    .btn-add-user { padding: 8px 20px; background: var(--glow-gold); color: #000; font-weight: bold; text-decoration: none; border-radius: 4px; cursor: pointer; border: none; transition: 0.3s; }
    .btn-add-user:hover { background: #fff; box-shadow: 0 0 15px var(--glow-gold); }
    .um-search-vault { margin-bottom: 40px; text-align: center; position: relative; }
    .um-input-search-glow { width: 100%; max-width: 700px; padding: 15px 25px; background: rgba(0, 0, 0, 0.7); border: 1px solid #444; border-radius: 50px; color: #fff; outline: none; transition: 0.3s; }
    .um-input-search-glow:focus { border-color: var(--glow-blue); box-shadow: 0 0 20px rgba(0, 212, 255, 0.3); }
    .um-quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 40px; }
    .quick-card { background: var(--card-bg); padding: 20px; border-radius: 8px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.05); cursor: pointer; transition: 0.3s; }
    .quick-card:hover { border-color: var(--glow-blue); transform: translateY(-3px); background: rgba(255, 255, 255, 0.08); }
    #search-results-overlay { width: 100%; max-width: 700px; background: #16161c; border: 1px solid #333; border-radius: 10px; display: none; position: absolute; left: 50%; transform: translateX(-50%); z-index: 100; max-height: 300px; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .result-item { padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; text-align: left; }
    .result-item:hover { background: rgba(255,255,255,0.1); }
    .um-table { width: 100%; border-collapse: collapse; color: #ccc; }
    .um-table th { text-align: left; color: var(--glow-gold); padding: 10px; border-bottom: 1px solid #444; }
    .um-table td { padding: 12px 10px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .btn-nexus-edit { background: transparent; border: 1px solid var(--glow-blue); color: var(--glow-blue); padding: 5px 10px; cursor: pointer; border-radius: 4px; }
</style>

<div class="um-nexus-wrapper">
    <div class="um-internal-header">
        <h2 class="um-internal-title">User Database</h2>
        <?php if ($_SESSION['priv_level'] >= 4): ?>
            <button onclick="loadNewUserForm()" class="btn-add-user">ADD USER</button>
        <?php endif; ?>
    </div>

    <div class="um-search-vault">
        <input type="text" id="nexus-live-search" class="um-input-search-glow" placeholder="Search by Username, Email or IP..." autocomplete="off">
        <div id="search-results-overlay"></div>
    </div>

    <div class="um-quick-grid">
        <div class="quick-card" onclick="loadCategory('all')"><h3>User List</h3></div>
        <div class="quick-card" onclick="loadCategory('restricted')"><h3 style="color:var(--error-red)">Restricted</h3></div>
        <div class="quick-card" onclick="loadCategory('warned')"><h3 style="color:var(--warning-orange)">Warned</h3></div>
        <?php if ($is_super_user): ?>
            <div class="quick-card" onclick="loadCategory('staff')"><h3 style="color:var(--glow-blue)">Staff</h3></div>
        <?php endif; ?>
    </div>

    <div id="nexus-ajax-container"></div>
</div>

<script>
    const searchInput = document.getElementById('nexus-live-search');
    const resultsOverlay = document.getElementById('search-results-overlay');
    const container = document.getElementById('nexus-ajax-container');
    const CSRF_TOKEN = '<?php echo $ajax_token; ?>';

    function umFetch(params, callback) {
        params.append('can_edit', '1');
        params.append('csrf_token', CSRF_TOKEN);
        fetch('modules/um_sync_worker.php', { method: 'POST', body: params })
        .then(res => res.text()).then(data => callback(data))
        .catch(err => alert('Nexus Protocol Error: ' + err));
    }

    searchInput.addEventListener('input', function() {
        if(this.value.length > 1) {
            const p = new URLSearchParams();
            p.append('um_ajax_search', this.value);
            umFetch(p, (data) => { resultsOverlay.innerHTML = data; resultsOverlay.style.display = 'block'; });
        } else { resultsOverlay.style.display = 'none'; }
    });

    function loadCategory(cat) {
        resultsOverlay.style.display = 'none';
        container.innerHTML = '<p style="text-align:center; color:var(--glow-gold);">Accessing Database Protocol...</p>';
        const p = new URLSearchParams();
        p.append('um_load_cat', cat);
        umFetch(p, (data) => { container.innerHTML = data; });
    }

    function loadUserEditor(id) {
        resultsOverlay.style.display = 'none';
        container.innerHTML = '<p style="text-align:center; color:var(--glow-blue);">Opening Nexus Editor for ID: ' + id + '...</p>';
        const p = new URLSearchParams();
        p.append('um_ajax_get_editor', id);
        umFetch(p, (data) => { container.innerHTML = data; });
    }

    function loadNewUserForm() {
        resultsOverlay.style.display = 'none';
        container.innerHTML = '<p style="text-align:center; color:var(--glow-gold);">Initializing New Entry Protocol...</p>';
        const p = new URLSearchParams();
        p.append('um_ajax_get_add_form', '1');
        umFetch(p, (data) => { container.innerHTML = data; });
    }

    document.addEventListener('click', function(e) { if (e.target !== searchInput && !resultsOverlay.contains(e.target)) { resultsOverlay.style.display = 'none'; } });
</script>