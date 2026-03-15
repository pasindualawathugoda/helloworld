<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
$settings = getSetting($pdo);

$editId = (int)($_GET['edit'] ?? 0);
$editChannel = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $editChannel = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();
$channels = $pdo->query("SELECT c.*, cat.name AS category_name FROM channels c LEFT JOIN categories cat ON cat.id = c.category_id ORDER BY c.sort_order ASC, c.id DESC")->fetchAll();

include __DIR__ . '/../includes/admin-header.php';
include __DIR__ . '/../includes/admin-sidebar.php';
?>
<div class="admin-main">
    <div class="admin-topbar">
        <h1>Manage Channels</h1>
        <a href="channels.php" class="btn">Add New</a>
    </div>

    <div class="admin-card">
        <h3 style="margin-bottom:16px;"><?= $editChannel ? 'Edit Channel' : 'Add Channel' ?></h3>
        <form method="post" action="channel-save.php">
            <input type="hidden" name="id" value="<?= $editChannel['id'] ?? '' ?>">
            <div class="admin-form-grid">
                <div><input type="text" name="channel_key" placeholder="Channel Key" value="<?= htmlspecialchars($editChannel['channel_key'] ?? '') ?>" required></div>
                <div><input type="text" name="channel_name" placeholder="Channel Name" value="<?= htmlspecialchars($editChannel['channel_name'] ?? '') ?>" required></div>
                <div><input type="text" name="channel_image" placeholder="Channel Image URL" value="<?= htmlspecialchars($editChannel['channel_image'] ?? '') ?>"></div>
                <div><input type="text" name="thumbnail" placeholder="Thumbnail URL" value="<?= htmlspecialchars($editChannel['thumbnail'] ?? '') ?>"></div>
                <div><input type="text" name="type" placeholder="Type" value="<?= htmlspecialchars($editChannel['type'] ?? 'IPTV') ?>"></div>
                <div><input type="text" name="epg_id" placeholder="EPG ID" value="<?= htmlspecialchars($editChannel['epg_id'] ?? '') ?>"></div>
                <div><input type="number" name="real_epg_id" placeholder="Real EPG ID" value="<?= htmlspecialchars($editChannel['real_epg_id'] ?? '') ?>"></div>
                <div>
                    <select name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ((string)($editChannel['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full"><input type="text" name="now_title" placeholder="Now Playing Title" value="<?= htmlspecialchars($editChannel['now_title'] ?? '') ?>"></div>
                <div><input type="text" name="views_text" placeholder="Views" value="<?= htmlspecialchars($editChannel['views_text'] ?? '') ?>"></div>
                <div><input type="text" name="live_time" placeholder="LIVE" value="<?= htmlspecialchars($editChannel['live_time'] ?? 'LIVE') ?>"></div>
                <div class="full"><textarea name="manifest_api" placeholder="Channel API URL" required><?= htmlspecialchars($editChannel['manifest_api'] ?? '') ?></textarea></div>
                <div class="full"><textarea name="license_proxy_url" placeholder="License Proxy URL"><?= htmlspecialchars($editChannel['license_proxy_url'] ?? ($settings['wv_license_proxy_url'] ?? '')) ?></textarea></div>
                <div><input type="number" name="sort_order" placeholder="Sort Order" value="<?= htmlspecialchars($editChannel['sort_order'] ?? 0) ?>"></div>
                <div>
                    <select name="is_live">
                        <option value="1" <?= ((string)($editChannel['is_live'] ?? '1') === '1') ? 'selected' : '' ?>>Live</option>
                        <option value="0" <?= ((string)($editChannel['is_live'] ?? '') === '0') ? 'selected' : '' ?>>Offline</option>
                    </select>
                </div>
                <div>
                    <select name="status">
                        <option value="1" <?= ((string)($editChannel['status'] ?? '1') === '1') ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ((string)($editChannel['status'] ?? '') === '0') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="full"><button type="submit">Save Channel</button></div>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h3 style="margin-bottom:16px;">All Channels</h3>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Widevine</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($channels as $ch): ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($ch['channel_image']) ?>" alt=""></td>
                            <td><?= htmlspecialchars($ch['channel_name']) ?></td>
                            <td><?= htmlspecialchars($ch['category_name'] ?? 'Other') ?></td>
                            <td><?= (int)$ch['drm_widevine'] === 1 ? 'Yes' : 'No' ?></td>
                            <td><?= (int)$ch['status'] === 1 ? 'Active' : 'Inactive' ?></td>
                            <td>
                                <a href="channels.php?edit=<?= (int)$ch['id'] ?>">Edit</a> |
                                <a href="channel-delete.php?id=<?= (int)$ch['id'] ?>" onclick="return confirm('Delete channel?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</body>
</html>