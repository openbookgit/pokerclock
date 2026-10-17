<?php
require_once __DIR__ . '/includes/storage.php';
$state = load_state();
$tournaments = $state['tournaments'] ?? [];
$keys = array_keys($tournaments);
$settings = $state['settings'] ?? [];

$leftId = $_GET['left'] ?? ($settings['dual_left_id'] ?? ($keys[0] ?? 'tourney_1'));
$rightId = $_GET['right'] ?? ($settings['dual_right_id'] ?? ($keys[1] ?? ($keys[0] ?? 'tourney_1')));

if (!isset($tournaments[$leftId])) $leftId = $keys[0];
if (!isset($tournaments[$rightId])) $rightId = $keys[min(1, count($keys) - 1)];

$leftT = $tournaments[$leftId];
$rightT = $tournaments[$rightId];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>雙場賽事顯示 - <?= htmlspecialchars($settings['brand_name'] ?? 'POKER CLOCK') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="tv-body" id="tvBody">
    <div class="tv-background"></div>
    <canvas id="fireCanvas"></canvas>

    <div class="tv-wrapper">
        <!-- Top Header (Image 3 layout) -->
        <header class="dual-header">
            <a href="index.php" class="brand-logo" title="返回賽事主控台">
                <span class="spade-icon" id="brandIcon"><?= htmlspecialchars($settings['brand_logo_icon'] ?? '♠') ?></span>
                <span id="brandName"><?= htmlspecialchars($settings['brand_name'] ?? 'POKER CLOCK') ?></span>
            </a>

            <div class="dual-header-right" id="btnDualSound" title="點擊切換雙場音效靜音/開啟">
                雙場賽事 • <span id="dualSoundStatus"><?= !empty($settings['dual_muted']) ? '音效全靜音' : '音效已開啟' ?></span>
            </div>
        </header>

        <!-- Dual Split Grid -->
        <main class="dual-grid">
            <?php foreach (['left' => $leftT, 'right' => $rightT] as $side => $t): ?>
                <section class="dual-card theme-<?= htmlspecialchars($t['theme'] ?? 'gold') ?>" id="dualCard_<?= $side ?>">
                    <div class="dual-title-section">
                        <h2 class="dual-title" id="<?= $side ?>_title"><?= htmlspecialchars($t['title']) ?></h2>
                        <div class="dual-subtitle" id="<?= $side ?>_subtitle"><?= htmlspecialchars($t['subtitle']) ?></div>
                    </div>

                    <div class="dual-timer-section">
                        <div class="dual-timer-side-label">
                            <span>本級剩</span>
                            <span>餘時間</span>
                            <span class="dual-timer-side-status" id="<?= $side ?>_status">進行中</span>
                        </div>
                        <div class="dual-timer-digits" id="<?= $side ?>_timer" data-side="<?= $side ?>" title="點擊切換 開始 / 暫停">00:00</div>
                    </div>

                    <div class="timer-progress-track" style="margin-bottom: 0; height: 3px;">
                        <div class="timer-progress-bar" id="<?= $side ?>_progress" style="width: 100%;"></div>
                    </div>

                    <div class="dual-subtimes-row">
                        <div class="dual-subtime-cell">
                            <div class="dual-subtime-label">距離賽事結束</div>
                            <div class="dual-subtime-val" id="<?= $side ?>_timeToEnd">--:--</div>
                        </div>
                        <div class="dual-subtime-cell">
                            <div class="dual-subtime-label">距離休息</div>
                            <div class="dual-subtime-val" id="<?= $side ?>_timeToBreak">--:--</div>
                        </div>
                    </div>

                    <div class="dual-blinds-row">
                        <div class="dual-blinds-label">
                            小盲/大盲 <small id="<?= $side ?>_levelTag">Level 01</small>
                        </div>
                        <div class="dual-blinds-val" id="<?= $side ?>_blinds">0/0</div>
                    </div>

                    <div class="dual-bottom-stats">
                        <div class="dual-stat-cell">
                            <div class="dual-stat-label">當前/參賽</div>
                            <div class="dual-stat-main" id="<?= $side ?>_players">0/0</div>
                        </div>
                        <div class="dual-stat-cell">
                            <div class="dual-stat-label">平均計分牌</div>
                            <div class="dual-stat-main" id="<?= $side ?>_avgChips">0</div>
                            <div class="dual-stat-sub" id="<?= $side ?>_avgBB">(0bb)</div>
                        </div>
                        <div class="dual-stat-cell">
                            <div class="dual-stat-label">獎池價值</div>
                            <div class="dual-stat-main" id="<?= $side ?>_prize">0</div>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Bottom Right Corner Badge: 火焰 : 開 -->
    <div class="bottom-corner-bar">
        <button type="button" class="fire-badge" id="btnToggleFire">
            火焰 : <?= !empty($settings['fire_effect']) ? '開' : '關' ?>
        </button>
    </div>

    <!-- Floating Quick Control Bar -->
    <div class="floating-controls" id="floatingControls">
        <span style="font-size:0.85rem; color:#aaa;">左場:</span>
        <select id="dualSelectLeft" class="ctrl-pill-btn" style="background:#141a24;">
            <?php foreach ($tournaments as $tid => $tItem): ?>
                <option value="<?= htmlspecialchars($tid) ?>" <?= $tid === $leftId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tItem['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="ctrl-pill-btn" id="btnLeftPlay">左 開始/暫停</button>

        <span style="width: 1px; height: 26px; background: rgba(255,255,255,0.2);"></span>

        <span style="font-size:0.85rem; color:#aaa;">右場:</span>
        <select id="dualSelectRight" class="ctrl-pill-btn" style="background:#141a24;">
            <?php foreach ($tournaments as $tid => $tItem): ?>
                <option value="<?= htmlspecialchars($tid) ?>" <?= $tid === $rightId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tItem['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="ctrl-pill-btn" id="btnRightPlay">右 開始/暫停</button>

        <span style="width: 1px; height: 26px; background: rgba(255,255,255,0.2);"></span>

        <a href="display.php?id=<?= urlencode($leftId) ?>" class="ctrl-pill-btn">單場畫面</a>
        <a href="admin.php" class="ctrl-pill-btn">⚙️ 後台控台</a>
        <button type="button" class="ctrl-btn" id="ctrlFullscreen" title="全螢幕切換 (F)">⛶</button>
    </div>

    <script src="assets/js/sound.js"></script>
    <script>
        window.DUAL_MODE = true;
        window.DUAL_LEFT_ID = <?= json_encode($leftId) ?>;
        window.DUAL_RIGHT_ID = <?= json_encode($rightId) ?>;
        window.INITIAL_STATE = <?= json_encode($state, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/display.js"></script>
</body>
</html>
