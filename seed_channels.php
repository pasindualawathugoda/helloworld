<?php
require_once __DIR__ . '/config/db.php';

$file = __DIR__ . '/channels.json';
if (!file_exists($file)) {
    exit('channels.json not found');
}

$json = file_get_contents($file);
$data = json_decode($json, true);
if (!is_array($data)) {
    exit('Invalid JSON');
}

$getCat = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
$addCat = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");

$insert = $pdo->prepare("INSERT INTO channels (
    channel_key, channel_name, channel_image, type, epg_id, real_epg_id, category_id,
    now_title, thumbnail, views_text, live_time, is_live, manifest_api,
    license_proxy_url, drm_widevine, drm_verimatrix, drm_fairplay, status, sort_order
) VALUES (
    :channel_key, :channel_name, :channel_image, :type, :epg_id, :real_epg_id, :category_id,
    :now_title, :thumbnail, :views_text, :live_time, :is_live, :manifest_api,
    :license_proxy_url, 0, 0, 0, 1, :sort_order
)");

$sort = 1;
foreach ($data as $item) {
    $catName = trim($item['category'] ?? 'Other');
    $getCat->execute([':name' => $catName]);
    $cat = $getCat->fetch();

    if (!$cat) {
        $addCat->execute([':name' => $catName]);
        $categoryId = $pdo->lastInsertId();
    } else {
        $categoryId = $cat['id'];
    }

    $insert->execute([
        ':channel_key' => $item['id'] ?? '',
        ':channel_name' => $item['channel'] ?? '',
        ':channel_image' => $item['channelImage'] ?? '',
        ':type' => $item['type'] ?? 'IPTV',
        ':epg_id' => $item['epgID'] ?? null,
        ':real_epg_id' => $item['realepgId'] ?? null,
        ':category_id' => $categoryId,
        ':now_title' => html_entity_decode($item['title'] ?? ''),
        ':thumbnail' => $item['thumbnail'] ?? '',
        ':views_text' => $item['views'] ?? '',
        ':live_time' => $item['time'] ?? 'LIVE',
        ':is_live' => !empty($item['isLive']) ? 1 : 0,
        ':manifest_api' => $item['manifest'] ?? '',
        ':license_proxy_url' => 'https://api.viulk.xyz/api/api/license',
        ':sort_order' => $sort++,
    ]);
}

echo 'Channels imported successfully';