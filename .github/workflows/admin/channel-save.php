<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_POST['id'] ?? 0);
$data = [
    ':channel_key' => trim($_POST['channel_key'] ?? ''),
    ':channel_name' => trim($_POST['channel_name'] ?? ''),
    ':channel_image' => trim($_POST['channel_image'] ?? ''),
    ':type' => trim($_POST['type'] ?? 'IPTV'),
    ':epg_id' => trim($_POST['epg_id'] ?? ''),
    ':real_epg_id' => ($_POST['real_epg_id'] ?? '') !== '' ? (int)$_POST['real_epg_id'] : null,
    ':category_id' => ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null,
    ':now_title' => trim($_POST['now_title'] ?? ''),
    ':thumbnail' => trim($_POST['thumbnail'] ?? ''),
    ':views_text' => trim($_POST['views_text'] ?? ''),
    ':live_time' => trim($_POST['live_time'] ?? 'LIVE'),
    ':is_live' => (int)($_POST['is_live'] ?? 1),
    ':manifest_api' => trim($_POST['manifest_api'] ?? ''),
    ':license_proxy_url' => trim($_POST['license_proxy_url'] ?? ''),
    ':status' => (int)($_POST['status'] ?? 1),
    ':sort_order' => (int)($_POST['sort_order'] ?? 0),
];

if ($id > 0) {
    $sql = "UPDATE channels SET
        channel_key = :channel_key,
        channel_name = :channel_name,
        channel_image = :channel_image,
        type = :type,
        epg_id = :epg_id,
        real_epg_id = :real_epg_id,
        category_id = :category_id,
        now_title = :now_title,
        thumbnail = :thumbnail,
        views_text = :views_text,
        live_time = :live_time,
        is_live = :is_live,
        manifest_api = :manifest_api,
        license_proxy_url = :license_proxy_url,
        status = :status,
        sort_order = :sort_order
        WHERE id = :id";
    $data[':id'] = $id;
} else {
    $sql = "INSERT INTO channels (
        channel_key, channel_name, channel_image, type, epg_id, real_epg_id, category_id,
        now_title, thumbnail, views_text, live_time, is_live, manifest_api,
        license_proxy_url, status, sort_order
    ) VALUES (
        :channel_key, :channel_name, :channel_image, :type, :epg_id, :real_epg_id, :category_id,
        :now_title, :thumbnail, :views_text, :live_time, :is_live, :manifest_api,
        :license_proxy_url, :status, :sort_order
    )";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($data);
header('Location: channels.php');
exit;