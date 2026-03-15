<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit('DB Error: ' . $e->getMessage());
}

function getSetting(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch();
    return $row ?: [];
}

function fetchJsonUrl(string $url): ?array
{
    if (!$url) {
        return null;
    }

    // Try cURL first if available
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0'
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$error && $response !== false && $code >= 200 && $code < 400) {
            $json = json_decode($response, true);
            return is_array($json) ? $json : null;
        }
    }

    // Fallback to file_get_contents if allowed
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'ignore_errors' => true,
                'header' =>
                    "Accept: application/json\r\n" .
                    "User-Agent: Mozilla/5.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $json = json_decode($response, true);
            return is_array($json) ? $json : null;
        }
    }

    return null;
}