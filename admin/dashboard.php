<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$settings = getSetting($pdo);

$totalChannels = (int)$pdo->query("SELECT COUNT(*) FROM channels")->fetchColumn();
$liveChannels = (int)$pdo->query("SELECT COUNT(*) FROM channels WHERE is_live = 1 AND status = 1")->fetchColumn();
$widevineChannels = (int)$pdo->query("SELECT COUNT(*) FROM channels WHERE drm_widevine = 1")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

include __DIR__ . '/../includes/admin-header.php';
include __DIR__ . '/../includes/admin-sidebar.php';
?>
<div class="admin-main">
    <div class="admin-topbar">
        <h1>Dashboard</h1>
        <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
    </div>

    <div class="admin-form-grid">
        <div class="admin-card"><h3>Total Channels</h3><p><?= $totalChannels ?></p></div>
        <div class="admin-card"><h3>Live Channels</h3><p><?= $liveChannels ?></p></div>
        <div class="admin-card"><h3>Widevine Channels</h3><p><?= $widevineChannels ?></p></div>
        <div class="admin-card"><h3>Total Categories</h3><p><?= $totalCategories ?></p></div>
    </div>

    <div class="admin-card">
        <h3>Global License Proxy</h3>
        <p><?= htmlspecialchars($settings['wv_license_proxy_url'] ?? '-') ?></p>
    </div>
</div>
</div>
</body>
</html>