<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE settings SET
        site_name = :site_name,
        site_logo = :site_logo,
        header_logo = :header_logo,
        theme_color = :theme_color,
        accent_color = :accent_color,
        wv_license_proxy_url = :wv_license_proxy_url,
        home_title = :home_title,
        home_subtitle = :home_subtitle,
        footer_text = :footer_text
        WHERE id = 1");

    $stmt->execute([
        ':site_name' => trim($_POST['site_name'] ?? ''),
        ':site_logo' => trim($_POST['site_logo'] ?? ''),
        ':header_logo' => trim($_POST['header_logo'] ?? ''),
        ':theme_color' => trim($_POST['theme_color'] ?? '#6c5ce7'),
        ':accent_color' => trim($_POST['accent_color'] ?? '#00b894'),
        ':wv_license_proxy_url' => trim($_POST['wv_license_proxy_url'] ?? ''),
        ':home_title' => trim($_POST['home_title'] ?? ''),
        ':home_subtitle' => trim($_POST['home_subtitle'] ?? ''),
        ':footer_text' => trim($_POST['footer_text'] ?? ''),
    ]);

    header('Location: settings.php?saved=1');
    exit;
}

$settings = getSetting($pdo);
include __DIR__ . '/../includes/admin-header.php';
include __DIR__ . '/../includes/admin-sidebar.php';
?>
<div class="admin-main">
    <div class="admin-topbar">
        <h1>Settings</h1>
    </div>

    <div class="admin-card">
        <?php if (isset($_GET['saved'])): ?>
            <p style="color:#34d399;margin-bottom:12px;">Settings updated.</p>
        <?php endif; ?>
        <form method="post">
            <div class="admin-form-grid">
                <div><input type="text" name="site_name" placeholder="Site Name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"></div>
                <div><input type="text" name="site_logo" placeholder="Site Logo URL" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>"></div>
                <div><input type="text" name="header_logo" placeholder="Header Logo URL" value="<?= htmlspecialchars($settings['header_logo'] ?? '') ?>"></div>
                <div><input type="text" name="wv_license_proxy_url" placeholder="Global Widevine License URL" value="<?= htmlspecialchars($settings['wv_license_proxy_url'] ?? '') ?>"></div>
                <div><input type="text" name="theme_color" placeholder="#6c5ce7" value="<?= htmlspecialchars($settings['theme_color'] ?? '') ?>"></div>
                <div><input type="text" name="accent_color" placeholder="#00b894" value="<?= htmlspecialchars($settings['accent_color'] ?? '') ?>"></div>
                <div><input type="text" name="home_title" placeholder="Home Title" value="<?= htmlspecialchars($settings['home_title'] ?? '') ?>"></div>
                <div><input type="text" name="home_subtitle" placeholder="Home Subtitle" value="<?= htmlspecialchars($settings['home_subtitle'] ?? '') ?>"></div>
                <div class="full"><input type="text" name="footer_text" placeholder="Footer Text" value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>"></div>
                <div class="full"><button type="submit">Save Settings</button></div>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>