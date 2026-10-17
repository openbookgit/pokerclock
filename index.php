<?php
require_once __DIR__ . '/includes/storage.php';
$state = load_state();
$tournaments = $state['tournaments'] ?? [];
$settings = $state['settings'] ?? [];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['brand_name'] ?? 'POKER DOMAIN') ?> - 德州撲克賽事計時系統</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800&family=Noto+Sans+TC:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #080a0f;
            --card: #121722;
            --border: #263044;
            --gold: #f7d54e;
            --cyan: #2beaff;
            --red: #ff4757;
            --green: #2ed573;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at 50% 0%, #172033 0%, #080a0f 70%);
            color: #ffffff;
            font-family: 'Noto Sans TC', sans-serif;
            min-height: 100vh;
            padding: 36px 24px 60px;
        }
        .wrap {
            max-width: 1240px;
            margin: 0 auto;
        }
        .hero {
            text-align: center;
            padding: 28px 20px 40px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 36px;
        }
        .brand {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 3.5vw, 3rem);
            font-weight: 800;
            color: var(--gold);
            letter-spacing: 0.08em;
            text-shadow: 0 0 24px rgba(247, 213, 78, 0.45);
        }
        .hero p {
            color: #94a3b8;
            font-size: 1.05rem;
            margin-top: 10px;
        }
        .quick-launch {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 26px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1rem;
            text-decoration: none;
            border: 1.5px solid var(--border);
            background: #171e2e;
            color: #fff;
            transition: all 0.18s ease;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            border-color: var(--gold);
        }
        .btn-gold {
            background: linear-gradient(180deg, #ffe680 0%, #f5c542 100%);
            color: #0b0e14;
            border-color: #ffe680;
            box-shadow: 0 0 20px rgba(245, 197, 66, 0.4);
        }
        .btn-cyan {
            background: rgba(43, 234, 255, 0.14);
            color: var(--cyan);
            border-color: var(--cyan);
            box-shadow: 0 0 18px rgba(43, 234, 255, 0.25);
        }
        .section-title {
            font-size: 1.35rem;
            font-weight: 800;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 24px;
        }
        .t-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }
        .t-card.theme-gold { border-color: var(--gold); box-shadow: 0 0 20px rgba(247, 213, 78, 0.15); }
        .t-card.theme-cyan { border-color: var(--cyan); box-shadow: 0 0 20px rgba(43, 234, 255, 0.15); }
        .t-card.theme-red { border-color: var(--red); box-shadow: 0 0 20px rgba(255, 71, 87, 0.15); }
        .t-card.theme-emerald { border-color: var(--green); box-shadow: 0 0 20px rgba(46, 213, 115, 0.15); }

        .t-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        .t-title {
            font-size: 1.5rem;
            font-weight: 900;
        }
        .t-sub {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-top: 2px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
        }
        .badge.running { background: rgba(46,213,115,0.2); color: var(--green); border: 1px solid var(--green); }
        .badge.paused { background: rgba(247,213,78,0.15); color: var(--gold); border: 1px solid var(--gold); }

        .t-clock-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            background: #0a0d14;
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .t-time {
            font-size: 2.8rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .t-blinds {
            text-align: right;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .t-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            text-align: center;
            font-size: 0.88rem;
            color: #94a3b8;
        }
        .t-stats strong {
            display: block;
            font-size: 1.1rem;
            color: #fff;
            margin-top: 2px;
        }
        .t-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .t-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        .features-box {
            margin-top: 48px;
            background: rgba(18, 23, 34, 0.7);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            color: #cbd5e1;
            font-size: 0.92rem;
        }
        .features-box h3 {
            color: var(--gold);
            margin-bottom: 6px;
            font-size: 1.05rem;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <div class="brand">
                <?= htmlspecialchars($settings['brand_logo_icon'] ?? '♠') ?>
                <?= htmlspecialchars($settings['brand_name'] ?? 'POKER DOMAIN') ?>
            </div>
            <p>專業德州撲克賽事計時器系統 • 支援單場豪華電視看板 / 雙場分割畫面 / FreePokerClock 簡約模式 / 遠端同步控台</p>
            <div class="quick-launch">
                <a href="dual.php" class="btn btn-cyan">🗔 開啟雙場分割畫面 (圖3模式)</a>
                <a href="admin.php" class="btn btn-gold">⚙️ 進入賽事後台控台</a>
            </div>
        </div>

        <div class="section-title">
            <span>🔥 當前賽事看板選擇</span>
            <a href="admin.php" class="btn" style="padding:7px 14px; font-size:0.85rem;">+ 新增 / 管理賽事</a>
        </div>

        <div class="grid">
            <?php foreach ($tournaments as $tid => $t):
                $c = $t['computed'] ?? [];
                $cur = $c['current_level'] ?? ['type' => 'level', 'level' => 1, 'sb' => 0, 'bb' => 0, 'ante' => 0];
                $mins = floor(($t['remaining_seconds'] ?? 0) / 60);
                $secs = ($t['remaining_seconds'] ?? 0) % 60;
                $clockStr = sprintf('%02d:%02d', $mins, $secs);
            ?>
                <div class="t-card theme-<?= htmlspecialchars($t['theme'] ?? 'gold') ?>">
                    <div class="t-header">
                        <div>
                            <div class="t-title"><?= htmlspecialchars($t['title']) ?></div>
                            <div class="t-sub"><?= htmlspecialchars($t['subtitle']) ?></div>
                        </div>
                        <span class="badge <?= $t['status'] === 'running' ? 'running' : 'paused' ?>">
                            <?= $t['status'] === 'running' ? '進行中' : '暫停中' ?>
                        </span>
                    </div>

                    <div class="t-clock-row">
                        <div class="t-time"><?= $clockStr ?></div>
                        <div class="t-blinds">
                            <div style="color:#94a3b8; font-size:0.85rem;">
                                <?= $cur['type'] === 'break' ? '休息時間' : 'Level ' . htmlspecialchars($cur['level']) ?>
                            </div>
                            <div>
                                <?= $cur['type'] === 'break' ? 'BREAK' : number_format($cur['sb']) . ' / ' . number_format($cur['bb']) ?>
                            </div>
                            <div style="font-size:0.8rem; color:#94a3b8;">
                                前注: <?= number_format($cur['ante'] ?? 0) ?>
                            </div>
                        </div>
                    </div>

                    <div class="t-stats">
                        <div>當前 / 參賽<strong><?= intval($t['current_players']) ?> / <?= intval($t['total_entries']) ?></strong></div>
                        <div>平均計分牌<strong><?= number_format($c['avg_chips'] ?? 0) ?> (<?= $c['avg_bb'] ?? 0 ?>bb)</strong></div>
                        <div>獎池價值<strong><?= number_format($c['prize_pool'] ?? 0) ?></strong></div>
                    </div>

                    <div class="t-actions">
                        <a href="display.php?id=<?= urlencode($tid) ?>" class="btn btn-gold">📺 電視看板</a>
                        <a href="freeclock.php?id=<?= urlencode($tid) ?>" class="btn">⏱️ 簡約圓環版</a>
                        <a href="admin.php?id=<?= urlencode($tid) ?>" class="btn">⚙️ 控台設定</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="features-box">
            <div>
                <h3>📺 單場與雙場電視看板</h3>
                <p>完整還原 POKER DOMAIN 流金黑與電光藍霓虹視覺，支援單場三欄式看板（含自動輪播公告、平均BB、距離休息/賽事結束）以及雙場同螢幕分割顯示。</p>
            </div>
            <div>
                <h3>⌨️ 快捷鍵與懸浮控制列</h3>
                <p>在電視畫面移動滑鼠即可喚出懸浮控制列；亦支援鍵盤快捷鍵：<code>空白鍵</code> 開始/暫停、<code>←/→</code> 切換級別、<code>↑/↓</code> 增減玩家、<code>F</code> 全螢幕、<code>M</code> 靜音。</p>
            </div>
            <div>
                <h3>🔊 內建音效與中文語音</h3>
                <p>採用 Web Audio API 合成最後1分鐘提醒音、倒數5秒提示音、升盲鐘聲，並可開啟中文語音播報盲注級別，無須額外安裝音訊檔。</p>
            </div>
            <div>
                <h3>🖧 NAS 隨放即用免資料庫</h3>
                <p>純 PHP + JSON 檔案鎖定儲存架構，無需設定 MySQL，直接放入 Synology / QNAP NAS 的 Web 資料夾或透過 Docker 即可多人多螢幕即時同步。</p>
            </div>
        </div>
    </div>
</body>
</html>
