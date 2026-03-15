<?php
require_once __DIR__.'/config/db.php';

$channelId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$channelId){
    header("Location:index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM channels WHERE id=? AND status=1 LIMIT 1");
$stmt->execute([$channelId]);
$channel = $stmt->fetch();

if(!$channel){
    die("Channel not found");
}

$channelName = $channel['channel_name'];
$channelLogo = $channel['channel_image'];
$thumbnail = $channel['thumbnail'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
<title><?= htmlspecialchars($channelName) ?> | Zerocast</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.11/shaka-player.compiled.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

<style>
:root{
    --accent:#007AFF;
    --accent-glow:rgba(0,122,255,.4);
}
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Outfit',sans-serif;
    -webkit-tap-highlight-color:transparent;
}
html,body{
    background:#000;
    height:100%;
    overflow:hidden;
    color:#fff;
}
.ambient-bg{
    position:fixed;
    inset:0;
    background:url('<?= htmlspecialchars($channelLogo) ?>') center/cover;
    filter:blur(80px) brightness(.25);
    transform:scale(1.2);
    z-index:1;
}
#video-container{
    position:fixed;
    inset:0;
    background:#000;
    display:none;
    z-index:10;
}
#video-container.active{
    display:block;
}
#video{
    width:100%;
    height:100%;
    background:#000;
    object-fit:contain;
}
#video::-webkit-media-controls{
    display:none !important;
}
#loader-screen{
    position:fixed;
    inset:0;
    background:#000;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    z-index:10000;
}
.app-loader{
    width:50px;
    height:50px;
    border:4px solid rgba(0,122,255,.1);
    border-left-color:var(--accent);
    border-radius:50%;
    animation:spin .8s linear infinite;
}
.loader-text{
    margin-top:20px;
    font-size:11px;
    letter-spacing:5px;
    font-weight:800;
    color:var(--accent);
    animation:pulse 1.5s ease-in-out infinite;
}
@keyframes spin{
    to{transform:rotate(360deg)}
}
@keyframes pulse{
    0%,100%{opacity:.3;filter:blur(1px)}
    50%{opacity:1;filter:blur(0)}
}
.top-ui{
    position:absolute;
    top:0;
    left:0;
    right:0;
    padding:25px;
    display:none;
    justify-content:space-between;
    align-items:center;
    z-index:100;
    background:linear-gradient(to bottom, rgba(0,0,0,.65), transparent);
    transition:opacity .25s ease;
}
.control-btn{
    width:45px;
    height:45px;
    border-radius:50%;
    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.1);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    text-decoration:none;
    backdrop-filter:blur(15px);
    font-size:18px;
}
.fs-btn{
    position:fixed;
    right:24px;
    bottom:24px;
    width:58px;
    height:58px;
    border-radius:50%;
    background:rgba(0,0,0,.65);
    border:1px solid rgba(255,255,255,.12);
    color:#fff;
    display:none;
    align-items:center;
    justify-content:center;
    font-size:21px;
    z-index:120;
    backdrop-filter:blur(14px);
    box-shadow:0 8px 30px rgba(0,0,0,.35);
}
#fullscreen-exit-btn{
    position:fixed;
    right:24px;
    top:24px;
    width:52px;
    height:52px;
    border-radius:50%;
    background:rgba(0,0,0,.65);
    border:1px solid rgba(255,255,255,.12);
    color:#fff;
    display:none;
    align-items:center;
    justify-content:center;
    font-size:20px;
    z-index:130;
    backdrop-filter:blur(14px);
}
#tap-overlay{
    position:fixed;
    inset:0;
    z-index:125;
    display:none;
    background:transparent;
}
#errorBox{
    display:none;
    position:fixed;
    left:20px;
    right:20px;
    bottom:30px;
    z-index:1001;
    background:rgba(20,20,20,.95);
    border:1px solid rgba(255,255,255,.12);
    border-radius:18px;
    padding:16px;
    text-align:center;
}
#errorBox button{
    margin-top:12px;
    background:#007AFF;
    color:#fff;
    border:none;
    border-radius:12px;
    padding:12px 18px;
    font-weight:bold;
}
body.fullscreen-active .fs-btn{
    display:none !important;
}
body.fullscreen-active .top-ui{
    display:none !important;
}
</style>
</head>
<body>

<div class="ambient-bg"></div>

<div id="loader-screen">
    <div class="app-loader"></div>
    <div class="loader-text">ZEROCAST</div>
</div>

<div id="video-container">
    <video
        id="video"
        autoplay
        playsinline
        preload="auto"
        poster="<?= htmlspecialchars($thumbnail) ?>"
        disablepictureinpicture
        controlslist="nodownload noplaybackrate nofullscreen noremoteplayback"
    ></video>
</div>

<div class="top-ui" id="ui-top">
    <a class="control-btn" href="index.php">
        <i class="bi bi-chevron-left"></i>
    </a>

    <div style="text-align:center">
        <div style="font-weight:700;font-size:16px"><?= htmlspecialchars($channelName) ?></div>
        <div style="font-size:10px;color:var(--accent);font-weight:800;letter-spacing:1px">LIVE</div>
    </div>

    <div class="control-btn">
        <i class="bi bi-reception-4"></i>
    </div>
</div>

<button class="fs-btn" id="fs-btn" onclick="enterFullscreen()" type="button">
    <i class="bi bi-arrows-fullscreen"></i>
</button>

<div id="tap-overlay"></div>

<button id="fullscreen-exit-btn" type="button" onclick="exitFullscreenMode()">
    <i class="bi bi-fullscreen-exit"></i>
</button>

<div id="errorBox">
    <div id="errorText">Stream failed</div>
    <button onclick="startPlayer()">Retry</button>
</div>

<script>
let player = null;
let retryCount = 0;
const maxRetries = 10;
let pauseBlockEnabled = true;
let fsHideTimer = null;

function showLoader() {
    document.getElementById('loader-screen').style.display = 'flex';
    document.getElementById('errorBox').style.display = 'none';
}

function hideLoader() {
    document.getElementById('loader-screen').style.display = 'none';
}

function showError(text) {
    hideLoader();
    document.getElementById('errorText').innerText = text || 'Stream failed';
    document.getElementById('errorBox').style.display = 'block';
}

async function fetchPlayerData() {
    const url = 'api/player-data.php?id=<?= (int)$channelId ?>&t=' + Date.now();
    const response = await fetch(url, { cache: 'no-store' });
    return await response.json();
}

async function destroyPlayer() {
    if (player) {
        try {
            await player.destroy();
        } catch (e) {}
        player = null;
    }
}

async function tryLandscapeLock() {
    try {
        if (screen.orientation && screen.orientation.lock) {
            await screen.orientation.lock('landscape');
        }
    } catch (e) {}
}

async function tryUnlockOrientation() {
    try {
        if (screen.orientation && screen.orientation.unlock) {
            screen.orientation.unlock();
        }
    } catch (e) {}
}

function showFullscreenExitButtonTemporarily() {
    const btn = document.getElementById('fullscreen-exit-btn');
    if (!document.fullscreenElement) return;

    btn.style.display = 'flex';

    if (fsHideTimer) {
        clearTimeout(fsHideTimer);
    }

    fsHideTimer = setTimeout(() => {
        if (document.fullscreenElement) {
            btn.style.display = 'none';
        }
    }, 2200);
}

async function enterFullscreen() {
    const container = document.getElementById('video-container');
    try {
        if (!document.fullscreenElement) {
            await container.requestFullscreen();
            await tryLandscapeLock();
            showFullscreenExitButtonTemporarily();
        }
    } catch (e) {}
}

async function exitFullscreenMode() {
    try {
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        }
    } catch (e) {}
    await tryUnlockOrientation();
}

function handleFullscreenUi() {
    const body = document.body;
    const fsBtn = document.getElementById('fs-btn');
    const exitBtn = document.getElementById('fullscreen-exit-btn');
    const tapOverlay = document.getElementById('tap-overlay');
    const topUi = document.getElementById('ui-top');

    if (document.fullscreenElement) {
        body.classList.add('fullscreen-active');
        fsBtn.style.display = 'none';
        topUi.style.display = 'none';
        tapOverlay.style.display = 'block';
        showFullscreenExitButtonTemporarily();
    } else {
        body.classList.remove('fullscreen-active');
        exitBtn.style.display = 'none';
        tapOverlay.style.display = 'none';
        if (document.getElementById('video-container').classList.contains('active')) {
            topUi.style.display = 'flex';
            fsBtn.style.display = 'flex';
        }
        tryUnlockOrientation();
    }
}

async function tryLoadOnce() {
    const video = document.getElementById('video');
    const json = await fetchPlayerData();

    if (json.status !== 'ok') {
        throw new Error(json.message || 'Stream data error');
    }

    const stream = json.stream;

    await destroyPlayer();

    player = new shaka.Player(video);

    player.configure({
        streaming: {
            bufferingGoal: 20,
            rebufferingGoal: 2,
            bufferBehind: 10,
            retryParameters: {
                maxAttempts: 2,
                baseDelay: 300,
                backoffFactor: 1.5,
                fuzzFactor: 0.5,
                timeout: 10000
            }
        }
    });

    if (stream.widevine && stream.license) {
        player.configure({
            drm: {
                servers: {
                    'com.widevine.alpha': stream.license
                }
            }
        });
    }

    player.addEventListener('error', async function() {
        if (retryCount < maxRetries) {
            retryCount++;
            try {
                await startPlayer(true);
                return;
            } catch (e) {}
        }
        showError('Stream failed');
    });

    await player.load(stream.manifest);

    video.muted = false;
    video.volume = 1;

    try {
        await video.play();
    } catch (e) {
        await new Promise(r => setTimeout(r, 250));
        await video.play();
    }

    document.getElementById('video-container').classList.add('active');
    document.getElementById('ui-top').style.display = document.fullscreenElement ? 'none' : 'flex';
    document.getElementById('fs-btn').style.display = document.fullscreenElement ? 'none' : 'flex';

    hideLoader();
    retryCount = 0;
}

async function startPlayer(isAutoRetry = false) {
    showLoader();

    if (!isAutoRetry) {
        retryCount = 0;
    }

    let lastError = null;

    for (let i = 0; i < maxRetries; i++) {
        try {
            await tryLoadOnce();
            return;
        } catch (e) {
            lastError = e;
            await new Promise(r => setTimeout(r, 700));
        }
    }

    showError(lastError && lastError.message ? lastError.message : 'Stream failed');
}

document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('video');
    const tapOverlay = document.getElementById('tap-overlay');

    video.removeAttribute('controls');

    video.addEventListener('pause', function() {
        if (pauseBlockEnabled) {
            setTimeout(() => {
                video.play().catch(() => {});
            }, 100);
        }
    });

    video.addEventListener('stalled', function() {
        startPlayer(true).catch(() => {});
    });

    video.addEventListener('error', function() {
        startPlayer(true).catch(() => {});
    });

    tapOverlay.addEventListener('click', function() {
        if (document.fullscreenElement) {
            showFullscreenExitButtonTemporarily();
        }
    });

    document.addEventListener('fullscreenchange', handleFullscreenUi);

    startPlayer().catch(() => {
        showError('Stream failed');
    });
});
</script>

</body>
</html>