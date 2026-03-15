<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $settings = getSetting($pdo);

    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid channel id'
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = :id AND status = 1 LIMIT 1");
    $stmt->execute([':id' => $id]);
    $channel = $stmt->fetch();

    if (!$channel) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Channel not found',
            'debug' => [
                'requested_id' => $id
            ]
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $json = fetchJsonUrl($channel['manifest_api']);

    if (!$json || !is_array($json) || ($json['status'] ?? '') !== 'ok' || empty($json['data'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to fetch stream data',
            'debug' => [
                'channel_id' => (int)$channel['id'],
                'channel_key' => $channel['channel_key'],
                'manifest_api' => $channel['manifest_api'],
                'curl_enabled' => function_exists('curl_init'),
                'allow_url_fopen' => (bool)ini_get('allow_url_fopen')
            ]
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $data = $json['data'];
    $drm = is_array($data['drm'] ?? null) ? $data['drm'] : [];

    $mpdUrl = trim((string)($data['url'] ?? ''));
    $licenseUrl = trim((string)(
        $data['wv_license_proxy_url']
        ?? $channel['license_proxy_url']
        ?? ($settings['wv_license_proxy_url'] ?? '')
    ));

    if ($mpdUrl === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'MPD URL missing',
            'debug' => [
                'channel_id' => (int)$channel['id'],
                'channel_key' => $channel['channel_key']
            ]
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $update = $pdo->prepare("UPDATE channels SET
        views_text = :views_text,
        license_proxy_url = :license_proxy_url,
        drm_widevine = :drm_widevine,
        drm_verimatrix = :drm_verimatrix,
        drm_fairplay = :drm_fairplay
    WHERE id = :id");

    $update->execute([
        ':views_text' => (string)($data['views'] ?? $channel['views_text'] ?? ''),
        ':license_proxy_url' => $licenseUrl,
        ':drm_widevine' => !empty($drm['widevine']) ? 1 : 0,
        ':drm_verimatrix' => !empty($drm['verimatrix']) ? 1 : 0,
        ':drm_fairplay' => !empty($drm['fairplay']) ? 1 : 0,
        ':id' => (int)$channel['id'],
    ]);

    echo json_encode([
        'status' => 'ok',
        'channel' => [
            'id' => (int)$channel['id'],
            'key' => $channel['channel_key'],
            'name' => $channel['channel_name'],
            'image' => $channel['channel_image'],
            'title' => $channel['now_title'],
        ],
        'stream' => [
            'manifest' => $mpdUrl,
            'license' => $licenseUrl,
            'widevine' => !empty($drm['widevine']),
            'verimatrix' => !empty($drm['verimatrix']),
            'fairplay' => !empty($drm['fairplay']),
            'views' => (string)($data['views'] ?? ''),
            'type' => (string)($data['type'] ?? ''),
            'channel_uid' => (string)($data['channel_uid'] ?? ''),
            'cdn_used' => (string)($data['cdn_used'] ?? ''),
            'limit_token' => (string)($data['limit_token'] ?? '')
        ],
        'debug' => [
            'db_channel_id' => (int)$channel['id'],
            'db_channel_key' => $channel['channel_key'],
            'manifest_api' => $channel['manifest_api'],
            'mpd_url' => $mpdUrl,
            'license_url' => $licenseUrl
        ]
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_SLASHES);
}