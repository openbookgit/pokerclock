<?php
require_once __DIR__ . '/includes/storage.php';
$state = load_state();
$tournaments = $state['tournaments'] ?? [];
$requestedId = $_GET['id'] ?? '';
if (!$requestedId || !isset($tournaments[$requestedId])) {
    $keys = array_keys($tournaments);
    $requestedId = $keys[0] ?? 'tourney_1';
}
$tourney = $tournaments[$requestedId];
$settings = $state['settings'] ?? [];
$themeClass = 'theme-' . htmlspecialchars($tourney['theme'] ?? 'gold');
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tourney['title']) ?> - <?= htmlspecialchars($settings['brand_name'] ?? 'POKER CLOCK') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="tv-body <?= $themeClass ?>" id="tvBody">
    <!-- Background Layers -->
    <div class="tv-background"></div>
    <div class="tv-dots-left"></div>
    <div class="tv-dots-right"></div>
    <canvas id="fireCanvas"></canvas>

    <div class="tv-wrapper">
        <!-- Top Header -->
        <header class="tv-header">
            <a href="index.php" class="brand-logo" title="返回賽事主控台">
                <span class="spade-icon" id="brandIcon"><?= htmlspecialchars($settings['brand_logo_icon'] ?? '♠') ?></span>
                <span id="brandName"><?= htmlspecialchars($settings['brand_name'] ?? 'POKER CLOCK') ?></span>
            </a>

            <div class="header-titles">
                <h1 class="tourney-title" id="tourneyTitle"><?= htmlspecialchars($tourney['title']) ?></h1>
                <div class="tourney-subtitle" id="tourneySubtitle"><?= htmlspecialchars($tourney['subtitle']) ?></div>
            </div>

            <div class="header-actions">
                <button type="button" class="sound-pill <?= empty($tourney['sound_enabled']) ? 'muted' : '' ?>" id="btnToggleSound">
                    <span id="soundIcon"><?= !empty($tourney['sound_enabled']) ? '🔊' : '🔇' ?></span>
                    <span id="soundText"><?= !empty($tourney['sound_enabled']) ? '音效開' : '音效關' ?></span>
                </button>
            </div>
        </header>

        <!-- Main 3-Column Layout -->
        <main class="single-grid">
            <!-- Left Column: Announcements -->
            <section class="neon-card announcement-card">
                <div>
                    <div class="card-header-row">
                        <span>賽事公告</span>
                        <span id="annPageIndicator">1/1</span>
                    </div>
                    <div class="announcement-body">
                        <div class="announcement-slide-title" id="annSlideTitle">賽事規則</div>
                        <div class="announcement-slide-content" id="annSlideContent"></div>
                    </div>
                </div>
                <div class="announcement-footer" id="annFooter" style="display: none;"></div>
            </section>

            <!-- Center Column: Clock & Blinds -->
            <section class="neon-card clock-card">
                <div class="clock-top-bar">
                    <span id="levelLabel">Level 1</span>
                    <span class="clock-status-badge" id="statusLabel">進行中</span>
                </div>

                <div class="clock-sub-label" id="clockSubLabel">本級剩餘時間</div>

                <div class="main-timer-display" id="mainTimer" title="點擊切換 開始 / 暫停 (空白鍵)">00:00</div>

                <div class="timer-progress-track">
                    <div class="timer-progress-bar" id="progressBar" style="width: 100%;"></div>
                </div>

                <div class="blinds-row">
                    <div>
                        <div class="blind-item-label">小盲</div>
                        <div class="blind-item-val" id="sbValue">0</div>
                    </div>
                    <div class="blind-slash">/</div>
                    <div>
                        <div class="blind-item-label">大盲</div>
                        <div class="blind-item-val" id="bbValue">0</div>
                    </div>
                </div>

                <div class="info-split-row">
                    <span>大盲前注</span>
                    <span id="anteValue">0</span>
                </div>

                <div class="info-split-row">
                    <span>下一級</span>
                    <span id="nextLevelValue">無下一級</span>
                </div>
            </section>

            <!-- Right Column: Target Time & Tournament Info -->
            <section class="right-col">
                <div class="neon-card target-time-card" id="targetTimeCard" title="點擊切換顯示：距離休息 / 距離賽事結束" style="cursor: pointer;">
                    <div class="target-time-header" id="targetTimeHeader">距離賽事結束</div>
                    <div class="target-time-val" id="targetTimeValue">--:--</div>
                </div>

                <div class="neon-card stats-card">
                    <div class="card-header-row" style="justify-content: center;">
                        <span>參賽資訊</span>
                    </div>

                    <div class="stats-section">
                        <div class="stats-label">平均計分牌</div>
                        <div class="stats-value-avg" id="avgStackValue">0(0bb)</div>
                    </div>

                    <div class="stats-section">
                        <div class="stats-label">獎池價值</div>
                        <div class="stats-value-prize" id="prizePoolValue">0</div>
                    </div>

                    <div class="stats-section players-box">
                        <div class="players-ratio" id="playersRatioValue">0 / 0</div>
                        <div class="players-sublabel">當前 / 參賽</div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Bottom Right Corner Badge: 火焰 : 開 -->
    <div class="bottom-corner-bar">
        <button type="button" class="fire-badge" id="btnToggleFire">
            火焰 : <?= !empty($settings['fire_effect']) ? '開' : '關' ?>
        </button>
    </div>

    <!-- Floating Quick Control Bar (FreePokerClock Style, auto-hides on idle) -->
    <div class="floating-controls" id="floatingControls">
        <button type="button" class="ctrl-btn" id="ctrlPrevLvl" title="上一級別 (←)">⏮</button>
        <button type="button" class="ctrl-pill-btn" id="ctrlMinusMin" title="減少 1 分鐘">-1分</button>
        <button type="button" class="ctrl-btn play-btn" id="ctrlPlayPause" title="開始 / 暫停 (Space)">▶</button>
        <button type="button" class="ctrl-pill-btn" id="ctrlPlusMin" title="增加 1 分鐘">+1分</button>
        <button type="button" class="ctrl-btn" id="ctrlNextLvl" title="下一級別 (→)">⏭</button>

        <span style="width: 1px; height: 26px; background: rgba(255,255,255,0.2);"></span>

        <button type="button" class="ctrl-pill-btn" id="ctrlBuyin" title="新增買入 (+1 當前 & +1 參賽)">+參賽</button>
        <button type="button" class="ctrl-pill-btn" id="ctrlBust" title="玩家淘汰 (-1 當前)">-淘汰</button>

        <span style="width: 1px; height: 26px; background: rgba(255,255,255,0.2);"></span>

        <select id="ctrlSwitchTourney" class="ctrl-pill-btn" style="background:#141a24;" title="切換顯示賽事">
            <?php foreach ($tournaments as $tid => $tItem): ?>
                <option value="<?= htmlspecialchars($tid) ?>" <?= $tid === $requestedId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tItem['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <a href="dual.php" class="ctrl-pill-btn" title="切換雙場分割畫面">雙場畫面</a>
        <a href="admin.php?id=<?= urlencode($requestedId) ?>" class="ctrl-pill-btn" title="開啟後台控台">⚙️ 控台</a>
        <button type="button" class="ctrl-btn" id="ctrlFullscreen" title="全螢幕切換 (F)">⛶</button>
    </div>

    <script src="assets/js/sound.js"></script>
    <script>
        window.INITIAL_TOURNEY_ID = <?= json_encode($requestedId) ?>;
        window.INITIAL_STATE = <?= json_encode($state, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/display.js"></script>
</body>
</html>
