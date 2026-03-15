<aside class="admin-sidebar">
    <div class="logo-box">
        <?php if (!empty($settings['site_logo'])): ?>
            <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo">
        <?php endif; ?>
        <div>
            <strong><?= htmlspecialchars($settings['site_name'] ?? 'IPTV App') ?></strong><br>
            <small>Admin Panel</small>
        </div>
    </div>

    <a href="dashboard.php">Dashboard</a>
    <a href="channels.php">Manage Channels</a>
    <a href="settings.php">Settings</a>
    <a href="logout.php">Logout</a>
</aside>