<?php
require_once __DIR__ . '/../config/db.php';
$settings = getSetting($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name'] ?? 'IPTV App') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <header class="app-header">
        <div class="brand-wrap">
            <?php if (!empty($settings['header_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['header_logo']) ?>" class="brand-logo" alt="Logo">
            <?php elseif (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>" class="brand-logo" alt="Logo">
            <?php endif; ?>
            <div>
                <h1><?= htmlspecialchars($settings['site_name'] ?? 'IPTV App') ?></h1>
                <p><?= htmlspecialchars($settings['home_subtitle'] ?? '') ?></p>
            </div>
        </div>
    </header>