<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

/*
|--------------------------------------------------------------------------
| Basic security headers
|--------------------------------------------------------------------------
*/
header('X-Robots-Tag: noindex, nofollow', true);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
header("Cross-Origin-Resource-Policy: same-origin");

/*
|--------------------------------------------------------------------------
| Mobile-only access
|--------------------------------------------------------------------------
| Keeps desktop users out without changing the mobile UI.
*/
function isMobileDevice(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') {
        return false;
    }

    $keywords = [
        'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'windows phone',
        'opera mini', 'mobile', 'iemobile', 'webos', 'kindle', 'silk'
    ];

    $uaLower = strtolower($ua);

    foreach ($keywords as $keyword) {
        if (strpos($uaLower, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

if (!isMobileDevice()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('The requested URL was rejected. Please consult with your administrator.

Your support ID is: 8999963781159294196');
}

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/
$settings = getSetting($pdo);
$siteTitle = $settings['site_name'] ?? 'Zerocastor';
$siteLogo = !empty($settings['header_logo'])
    ? $settings['header_logo']
    : (!empty($settings['site_logo']) ? $settings['site_logo'] : 'https://zerocastor.com/assets/newlogo.png');

/*
|--------------------------------------------------------------------------
| Fetch slides
|--------------------------------------------------------------------------
| If you already have a slides table, this will use it.
| If not, it falls back to latest active channels.
*/
$slides = [];

try {
    $slidesStmt = $pdo->query("
        SELECT s.*, c.channel_name, c.channel_image, c.thumbnail
        FROM slides s
        LEFT JOIN channels c ON c.id = s.channel_id
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $slides = $slidesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $slides = [];
}

if (!$slides) {
    $fallbackStmt = $pdo->query("
        SELECT 
            id AS channel_id,
            channel_name AS title,
            thumbnail AS image_url,
            channel_name,
            channel_image,
            thumbnail
        FROM channels
        WHERE status = 1
        ORDER BY id DESC
        LIMIT 50
    ");
    $slides = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| Normalize slides so empty items do not break slider count
|--------------------------------------------------------------------------
*/
$normalizedSlides = [];
foreach ($slides as $s) {
    $slideChannelId = isset($s['channel_id']) ? (int)$s['channel_id'] : 0;
    $slideTitle = $s['title'] ?? ($s['channel_name'] ?? 'Featured Channel');
    $slideImage = $s['image_url'] ?? ($s['thumbnail'] ?? $s['channel_image'] ?? '');

    if ($slideChannelId > 0 && !empty($slideImage)) {
        $normalizedSlides[] = [
            'channel_id' => $slideChannelId,
            'title'      => $slideTitle,
            'image_url'  => $slideImage,
        ];
    }
}
$slides = $normalizedSlides;

/*
|--------------------------------------------------------------------------
| Fetch categories
|--------------------------------------------------------------------------
*/
$catsStmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$cats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

function getCategoryIcon(string $name): string
{
    $name = strtolower(trim($name));
    if (strpos($name, 'local') !== false) return 'bi-geo-alt-fill';
    if (strpos($name, 'news') !== false) return 'bi-broadcast-pin';
    if (strpos($name, 'sport') !== false) return 'bi-trophy-fill';
    if (strpos($name, 'movie') !== false) return 'bi-film';
    if (strpos($name, 'kids') !== false) return 'bi-balloon-fill';
    if (strpos($name, 'music') !== false) return 'bi-music-note-beamed';
    if (strpos($name, 'education') !== false) return 'bi-mortarboard-fill';
    return 'bi-play-circle-fill';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?> | Premium TV</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #000000;
            --surface: #0a0a0a;
            --accent: #007AFF;
            --accent-glow: rgba(0, 122, 255, 0.4);
            --glass: rgba(18, 18, 18, 0.85);
            --border: rgba(255, 255, 255, 0.1);
            --safe-bottom: env(safe-area-inset-bottom);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            outline: none;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            padding-bottom: calc(100px + var(--safe-bottom));
        }

        header {
            position: sticky;
            top: 0;
            z-index: 2000;
            padding: calc(env(safe-area-inset-top) + 15px) 20px 10px;
            background: linear-gradient(to bottom, black 50%, transparent);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand img {
            height: 28px;
            filter: drop-shadow(0 0 10px var(--accent-glow));
            max-width: 150px;
            object-fit: contain;
        }

        .search-pill {
            background: rgba(255,255,255,0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            padding: 8px 15px;
            flex: 1;
            margin-left: 15px;
            border: 1px solid var(--border);
        }

        .search-pill input {
            background: transparent;
            border: none;
            color: #fff;
            margin-left: 10px;
            width: 100%;
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
        }

        .search-pill input::placeholder {
            color: #aaa;
        }

        .hero-slider {
            position: relative;
            width: 100%;
            height: 260px;
            padding: 10px 15px;
            margin-bottom: 25px;
        }

        .slider-inner {
            width: 100%;
            height: 100%;
            border-radius: 30px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            border: 1px solid var(--border);
        }

        .slider-track {
            display: flex;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.45, 0, 0.55, 1);
        }

        .slide {
            min-width: 100%;
            height: 100%;
            position: relative;
            cursor: pointer;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slide-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, rgba(0,0,0,0.9) 5%, rgba(0,0,0,0.2) 50%, transparent 100%);
        }

        .slide-content {
            position: absolute;
            bottom: 30px;
            left: 25px;
            z-index: 5;
        }

        .slide-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .featured-tag {
            background: var(--accent);
            color: #fff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            box-shadow: 0 0 10px var(--accent-glow);
        }

        .live-tag {
            background: rgba(255, 59, 48, 0.2);
            backdrop-filter: blur(10px);
            color: #ff3b30;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            border: 1px solid rgba(255, 59, 48, 0.3);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .live-tag i {
            font-size: 6px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .slide-title {
            font-size: 26px;
            font-weight: 900;
            margin: 0;
            letter-spacing: -1px;
        }

        .slider-indicators {
            position: absolute;
            bottom: 15px;
            left: 25px;
            right: 25px;
            display: flex;
            gap: 6px;
            z-index: 10;
        }

        .ind-bar {
            height: 3px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            flex: 1;
            overflow: hidden;
        }

        .ind-progress {
            height: 100%;
            background: #fff;
            width: 0;
        }

        .ind-bar.active .ind-progress {
            animation: progressRun 5s linear forwards;
        }

        @keyframes progressRun {
            from { width: 0; }
            to { width: 100%; }
        }

        .container {
            padding: 0 15px;
        }

        .cat-section {
            margin-top: 35px;
            scroll-margin-top: 110px;
        }

        .cat-name {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cat-name i {
            color: var(--accent);
            filter: drop-shadow(0 0 8px var(--accent-glow));
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .channel-card {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon-box {
            width: 100%;
            aspect-ratio: 1/1;
            background: #000;
            border-radius: 24px;
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22%;
            position: relative;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .channel-card:active .icon-box,
        .channel-card:hover .icon-box {
            transform: scale(0.92);
            border-color: var(--accent);
            box-shadow: 0 0 15px var(--accent-glow);
        }

        .icon-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .label {
            margin-top: 10px;
            font-size: 11px;
            font-weight: 700;
            color: #888;
            text-align: center;
            transition: 0.3s;
            line-height: 1.3;
        }

        .channel-card:hover .label {
            color: var(--accent);
        }

        .spotify-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(75px + var(--safe-bottom));
            background: var(--glass);
            backdrop-filter: blur(30px) saturate(180%);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            padding-top: 12px;
            z-index: 3000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            flex: 1;
            transition: 0.3s;
        }

        .nav-item i {
            font-size: 24px;
            margin-bottom: 4px;
            transition: transform 0.3s;
        }

        .nav-item span {
            font-size: 10px;
            font-weight: 700;
        }

        .nav-item.active {
            color: #fff;
        }

        .nav-item.active i {
            color: var(--accent);
            transform: scale(1.1) translateY(-2px);
            filter: drop-shadow(0 0 8px var(--accent-glow));
        }

        /* Force mobile-only layout */
        @media (min-width: 768px) {
            body {
                max-width: 430px;
                margin: 0 auto;
                position: relative;
            }

            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .hero-slider {
                height: 260px;
            }

            .slide-title {
                font-size: 26px;
            }

            .spotify-nav {
                max-width: 430px;
                left: 50%;
                transform: translateX(-50%);
                border-radius: 25px 25px 0 0;
            }
        }
    </style>
</head>
<body id="home">

<header>
    <div class="brand">
        <img src="<?= htmlspecialchars($siteLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
    </div>
    <div class="search-pill">
        <i class="bi bi-search" style="color:var(--accent)"></i>
        <input type="text" id="q" placeholder="Search Channels..." onkeyup="searchChannels()">
    </div>
</header>

<div class="hero-slider">
    <div class="slider-inner">
        <div class="slider-track" id="track">
            <?php foreach ($slides as $s): ?>
                <div class="slide" onclick="location.href='watch.php?id=<?= (int)$s['channel_id'] ?>'">
                    <img src="<?= htmlspecialchars($s['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <div class="slide-badges">
                            <span class="featured-tag">FEATURED</span>
                            <span class="live-tag"><i class="bi bi-circle-fill"></i> LIVE</span>
                        </div>
                        <h3 class="slide-title"><?= htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="slider-indicators">
            <?php foreach ($slides as $i => $s): ?>
                <div class="ind-bar <?= $i === 0 ? 'active' : '' ?>">
                    <div class="ind-progress"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<main class="container">
    <?php foreach ($cats as $cat): ?>
        <?php
        $stmt = $pdo->prepare("
            SELECT c.*
            FROM channels c
            WHERE c.category_id = ? AND c.status = 1
            ORDER BY c.channel_name ASC
        ");
        $stmt->execute([(int)$cat['id']]);
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$channels) {
            continue;
        }
        ?>
        <div class="cat-section" id="cat-<?= (int)$cat['id'] ?>">
            <div class="cat-name">
                <i class="bi <?= htmlspecialchars(getCategoryIcon((string)$cat['name']), ENT_QUOTES, 'UTF-8') ?>"></i>
                <?= htmlspecialchars((string)$cat['name'], ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="grid-3">
                <?php foreach ($channels as $c): ?>
                    <a href="watch.php?id=<?= (int)$c['id'] ?>" class="channel-card" data-name="<?= htmlspecialchars(strtolower((string)$c['channel_name']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="icon-box">
                            <img src="<?= htmlspecialchars((string)$c['channel_image'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" alt="<?= htmlspecialchars((string)$c['channel_name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <span class="label"><?= htmlspecialchars((string)$c['channel_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<nav class="spotify-nav">
    <a href="#home" class="nav-item active"><i class="bi bi-house-door-fill"></i><span>Home</span></a>
    <?php
    $navCats = array_slice($cats, 0, 3);
    foreach ($navCats as $navCat):
    ?>
        <a href="#cat-<?= (int)$navCat['id'] ?>" class="nav-item">
            <i class="bi <?= htmlspecialchars(getCategoryIcon((string)$navCat['name']), ENT_QUOTES, 'UTF-8') ?>"></i>
            <span><?= htmlspecialchars((string)$navCat['name'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script>
    let cur = 0;
    const track = document.getElementById('track');
    const slidesCount = <?= (int)count($slides) ?>;
    const bars = document.querySelectorAll('.ind-bar');

    function updateSlider() {
        if (!track || slidesCount < 2) return;
        track.style.transform = `translateX(-${cur * 100}%)`;
        bars.forEach((bar, i) => bar.classList.toggle('active', i === cur));
    }

    if (slidesCount > 1) {
        setInterval(() => {
            cur = (cur + 1) % slidesCount;
            updateSlider();
        }, 5000);
    }

    function searchChannels() {
        const input = document.getElementById('q');
        const val = input ? input.value.toLowerCase() : '';

        document.querySelectorAll('.cat-section').forEach(section => {
            let anyVisible = false;

            section.querySelectorAll('.channel-card').forEach(card => {
                if ((card.dataset.name || '').includes(val)) {
                    card.style.display = 'flex';
                    anyVisible = true;
                } else {
                    card.style.display = 'none';
                }
            });

            section.style.display = anyVisible ? 'block' : 'none';
        });
    }

    window.addEventListener('scroll', () => {
        let current = 'home';

        document.querySelectorAll('.cat-section').forEach(sec => {
            if (window.pageYOffset >= sec.offsetTop - 250) {
                current = sec.getAttribute('id');
            }
        });

        document.querySelectorAll('.nav-item').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('href') === `#${current}`);
        });
    });
</script>
</body>
</html>