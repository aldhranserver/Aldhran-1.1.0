<aside class="sidebar">
    <nav>
        <h3><i class="fas fa-fortress-antiquity"></i> Stronghold</h3>
        <ul>
            <li><a href="index.php?p=home"><i class="fas fa-home"></i> Home</a></li>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="?p=register"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php endif; ?>
        </ul>

        <?php if (isset($_SESSION['user_id'])): ?>
            <h3><i class="fas fa-user-circle"></i> Account</h3>
            <ul>
                <li><a href="?p=profile"><i class="fas fa-id-card"></i> My Profile</a></li>
                <li><a href="logout.php" class="link-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        <?php endif; ?>

        <h3><i class="fas fa-search"></i> Search</h3>
        <div class="sidebar-search-container">
            <form action="index.php" method="GET" class="sidebar-search-form">
                <input type="hidden" name="p" value="search"> 
                <input type="text" name="q" 
                       class="sidebar-search-input" 
                       placeholder="Find a player..." 
                       value="<?php echo h($_GET['q'] ?? ''); ?>">
                <button type="submit" class="sidebar-search-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </form>

            <?php 
            $searchTerm = trim($_GET['q'] ?? '');
            if (!empty($searchTerm) && ($_GET['p'] ?? '') !== 'search'): ?>
                <div class="search-results-box">
                    <?php
                    $stmtSearch = $db->prepare("SELECT id, username FROM users WHERE username LIKE ? LIMIT 5");
                    $stmtSearch->execute(['%' . $searchTerm . '%']);
                    $searchResults = $stmtSearch->fetchAll();

                    if ($searchResults):
                        foreach($searchResults as $row): ?>
                            <a href="?p=user&id=<?php echo (int)$row['id']; ?>" class="search-result-link">
                                <i class="fas fa-user search-icon-dim"></i> <?php echo h($row['username']); ?>
                            </a>
                        <?php endforeach;
                    else: ?>
                        <span class="search-no-result">No user found.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <h3><i class="fas fa-gavel"></i> Terms & Rules</h3>
        <ul>
            <li><a href="?p=rules"><i class="fas fa-scroll"></i> Rules</a></li>
            <li><a href="?p=participation"><i class="fas fa-users-cog"></i> Participation</a></li>
        </ul>

        <h3><i class="fas fa-users"></i> Community</h3>
        <ul>
            <li><a href="?p=herald"><i class="fas fa-bullhorn"></i> Herald</a></li>
            <li><a href="?p=spike"><i class="fas fa-comments"></i> Forum</a></li>
            <li><a href="?p=team"><i class="fas fa-shield-alt"></i> Team</a></li>
            <li><a href="?p=faq"><i class="fas fa-question-circle"></i> FAQ</a></li>
        </ul>

        <h3><i class="fas fa-book-open"></i> Library</h3>
        <ul>
            <li><a href="?p=pve"><i class="fas fa-dragon"></i> PvE Database</a></li>
            <li><a href="?p=rvr"><i class="fas fa-khanda"></i> RvR Database</a></li>
            <li><a href="?p=map"><i class="fas fa-map-marked-alt"></i> RvR Map</a></li>
            <li><a href="?p=classes"><i class="fas fa-fist-raised"></i> Classes</a></li>
        </ul>

        <?php 
        $checkPriv = (int)($_SESSION['priv_level'] ?? 0);
        $canAdmin = ($checkPriv >= 4);

        if (!$canAdmin && ($checkPriv === 3 || $checkPriv === 2)) {
            $stmtPerm = $db->prepare("SELECT can_manage_users FROM staff_permissions WHERE priv_level = ?");
            $stmtPerm->execute([$checkPriv]);
            $rowP = $stmtPerm->fetch();
            if ($rowP && (int)$rowP['can_manage_users'] === 1) {
                $canAdmin = true;
            }
        }

        if ($canAdmin): ?>
            <h3 class="admin-section-title"><i class="fas fa-user-shield"></i> Admin Tools</h3>
            <ul>
                <li><a href="?p=um" class="admin-link"><i class="fas fa-users-gear"></i> User Manager</a></li>
                <?php if ($checkPriv >= 4): ?>
                    <li><a href="?p=spike_admin" class="admin-link"><i class="fas fa-comments"></i> Forum Architect</a></li>
                    <li><a href="?p=admin_log" class="admin-link"><i class="fas fa-history"></i> Admin Logs</a></li>
                    <li><a href="?p=admin_ip_audit" class="admin-link"><i class="fas fa-fingerprint"></i> Audit IP Log</a></li>
                    <li><a href="?p=maintenance_text" class="admin-link"><i class="fas fa-comment-dots"></i> Maintenance Text</a></li>
                    <li><a href="?p=faq_admin" class="admin-link"><i class="fas fa-question-circle"></i> FAQ Manager</a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </nav>
</aside>