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
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tourney['title']) ?> - FreePokerClock 簡約模式</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Noto+Sans+TC:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --fpc-red: #ff2e3b;
            --fpc-grey-1: #16181d;
            --fpc-grey-2: #262932;
            --fpc-grey-3: #3a3f4d;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #000000;
            color: #ffffff;
            font-family: 'Noto Sans TC', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            user-select: none;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 32px;
            border-bottom: 1px solid #1b1e26;
        }
        .fpc-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.9rem;
            letter-spacing: 0.06em;
            color: #fff;
            text-decoration: none;
        }
        .fpc-logo span { color: var(--fpc-red); }
        .fpc-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .fpc-btn-link {
            background: var(--fpc-grey-1);
            border: 1px solid var(--fpc-grey-3);
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .fpc-btn-link:hover {
            border-color: var(--fpc-red);
            color: var(--fpc-red);
        }
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 3.5vh;
            padding: 20px;
        }
        .fpc-title-bar {
            text-align: center;
        }
        .fpc-title-bar h1 {
            font-size: clamp(1.5rem, 2.8vw, 2.8rem);
            font-weight: 800;
        }
        .fpc-title-bar p {
            color: #9ca3af;
            font-size: 1rem;
            margin-top: 4px;
        }
        .circles-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4vw;
            flex-wrap: wrap;
        }
        .side-circle {
            width: clamp(180px, 22vh, 260px);
            height: clamp(180px, 22vh, 260px);
            border-radius: 50%;
            border: 0.9vh solid var(--fpc-grey-2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 20px;
            text-align: center;
        }
        .side-circle .label {
            font-size: clamp(0.9rem, 1.8vh, 1.3rem);
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .side-circle .blinds {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.8rem, 4.2vh, 3.2rem);
            line-height: 1;
            letter-spacing: 0.03em;
        }
        .side-circle .ante {
            font-size: clamp(0.85rem, 1.6vh, 1.15rem);
            color: #d1d5db;
        }
        .center-ring-wrap {
            position: relative;
            width: clamp(260px, 40vh, 440px);
            height: clamp(260px, 40vh, 440px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ring-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .ring-bg {
            fill: none;
            stroke: var(--fpc-grey-2);
            stroke-width: 8;
        }
        .ring-fg {
            fill: none;
            stroke: var(--fpc-red);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 283;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 0.4s linear;
        }
        .center-content {
            position: relative;
            z-index: 2;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .center-level {
            font-size: clamp(1.1rem, 2.2vh, 1.6rem);
            font-weight: 700;
            color: var(--fpc-red);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .center-timer {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(4.5rem, 12vh, 9.5rem);
            line-height: 0.95;
            letter-spacing: 0.04em;
            margin: 8px 0;
            cursor: pointer;
        }
        .center-status {
            font-size: clamp(0.9rem, 1.7vh, 1.2rem);
            color: #9ca3af;
        }
        .controls-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }
        .fpc-ctrl-btn {
            background: transparent;
            border: 1.5px solid var(--fpc-red);
            color: var(--fpc-red);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.15s;
        }
        .fpc-ctrl-btn:hover {
            background: var(--fpc-red);
            color: #000;
        }
        .fpc-ctrl-btn.play {
            width: 68px;
            height: 68px;
            font-size: 1.6rem;
        }
        .fpc-stats-strip {
            display: flex;
            gap: 32px;
            background: var(--fpc-grey-1);
            border: 1px solid var(--fpc-grey-2);
            padding: 12px 28px;
            border-radius: 999px;
            font-size: 0.95rem;
            color: #d1d5db;
        }
        .fpc-stats-strip strong {
            color: #fff;
            margin-left: 6px;
        }
        .fullscreen-fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--fpc-grey-1);
            border: 1px solid var(--fpc-grey-3);
            color: var(--fpc-red);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.3rem;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="fpc-logo">
            ♠ <span>THE POKER TIMER</span> FREE CLOCK
        </a>
        <div class="fpc-nav">
            <select id="switchTourney" class="fpc-btn-link">
                <?php foreach ($tournaments as $tid => $tItem): ?>
                    <option value="<?= htmlspecialchars($tid) ?>" <?= $tid === $requestedId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tItem['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="display.php?id=<?= urlencode($requestedId) ?>" class="fpc-btn-link">📺 切換豪華電視版</a>
            <a href="dual.php" class="fpc-btn-link">🗔 雙場分割畫面</a>
            <a href="admin.php?id=<?= urlencode($requestedId) ?>" class="fpc-btn-link">⚙️ 後台設定</a>
        </div>
    </header>

    <main>
        <div class="fpc-title-bar">
            <h1 id="fpcTitle"><?= htmlspecialchars($tourney['title']) ?></h1>
            <p id="fpcSubtitle"><?= htmlspecialchars($tourney['subtitle']) ?></p>
        </div>

        <div class="circles-row">
            <!-- Current Blinds Circle -->
            <div class="side-circle">
                <div class="label">Blinds 當前盲注</div>
                <div class="blinds" id="fpcBlinds">0 / 0</div>
                <div class="ante">Ante 前注: <span id="fpcAnte">0</span></div>
            </div>

            <!-- Center Timer Ring -->
            <div class="center-ring-wrap">
                <svg class="ring-svg" viewBox="0 0 100 100">
                    <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="ring-fg" id="fpcRing" cx="50" cy="50" r="45"></circle>
                </svg>
                <div class="center-content">
                    <div class="center-level" id="fpcLevel">LEVEL 1</div>
                    <div class="center-timer" id="fpcTimer" title="點擊開始/暫停">00:00</div>
                    <div class="center-status" id="fpcStatus">PAUSED</div>
                </div>
            </div>

            <!-- Next Blinds Circle -->
            <div class="side-circle">
                <div class="label">Next 下一級別</div>
                <div class="blinds" id="fpcNextBlinds">0 / 0</div>
                <div class="ante">Ante 前注: <span id="fpcNextAnte">0</span></div>
            </div>
        </div>

        <!-- Red Controls (FreePokerClock layout) -->
        <div class="controls-bar">
            <button class="fpc-ctrl-btn" id="btnSound" title="音效開關">🔊</button>
            <button class="fpc-ctrl-btn" id="btnPrev" title="上一級">⏮</button>
            <button class="fpc-ctrl-btn play" id="btnPlay" title="開始 / 暫停">▶</button>
            <button class="fpc-ctrl-btn" id="btnNext" title="下一級">⏭</button>
            <a href="admin.php?id=<?= urlencode($requestedId) ?>" class="fpc-ctrl-btn" style="text-decoration:none;" title="設定">⚙</a>
        </div>

        <div class="fpc-stats-strip">
            <div>參賽人數: <strong id="fpcPlayers">0 / 0</strong></div>
            <div>平均計分牌: <strong id="fpcAvg">0 (0bb)</strong></div>
            <div>總獎池: <strong id="fpcPrize">0</strong></div>
            <div>距離休息: <strong id="fpcBreak">--:--</strong></div>
        </div>
    </main>

    <button class="fullscreen-fab" id="btnFullscreen" title="全螢幕">⛶</button>

    <script src="assets/js/sound.js"></script>
    <script>
        let state = <?= json_encode($state, JSON_UNESCAPED_UNICODE) ?>;
        let tourneyId = <?= json_encode($requestedId) ?>;
        let lastLevelIdx = null;
        let lastBeepSec = null;

        function fmtNum(n) { return (Number(n) || 0).toLocaleString('en-US'); }
        function fmtTime(sec) {
            if (sec === null || sec === undefined || sec < 0) return '--:--';
            const s = Math.max(0, Math.floor(sec));
            const m = Math.floor(s / 60);
            const r = s % 60;
            return (m < 10 ? '0' + m : m) + ':' + (r < 10 ? '0' + r : r);
        }

        async function apiCall(payload) {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json && json.ok) { state = json.data; render(); }
        }

        function render() {
            const t = state.tournaments[tourneyId];
            if (!t) return;
            const c = t.computed || {};
            const cur = c.current_level || {};
            const nxt = c.next_level || null;

            if (lastLevelIdx !== null && lastLevelIdx !== t.current_level_index && t.sound_enabled) {
                PokerAudio.playLevelUp();
            }
            lastLevelIdx = t.current_level_index;

            if (t.status === 'running' && t.sound_enabled && lastBeepSec !== t.remaining_seconds) {
                lastBeepSec = t.remaining_seconds;
                if (t.remaining_seconds === 60) PokerAudio.playWarning();
                else if (t.remaining_seconds <= 5 && t.remaining_seconds >= 1) PokerAudio.playTick();
            }

            document.getElementById('fpcTitle').textContent = t.title;
            document.getElementById('fpcSubtitle').textContent = t.subtitle;
            document.getElementById('fpcLevel').textContent = cur.type === 'break' ? 'BREAK 休息' : ('LEVEL ' + cur.level);
            document.getElementById('fpcTimer').textContent = fmtTime(t.remaining_seconds);
            document.getElementById('fpcStatus').textContent = t.status === 'running' ? '進行中 RUNNING' : '暫停中 PAUSED';

            const pct = c.progress_percent ?? 100;
            const circumference = 283;
            document.getElementById('fpcRing').style.strokeDashoffset = circumference - (circumference * pct / 100);

            document.getElementById('fpcBlinds').textContent = cur.type === 'break' ? 'BREAK' : `${fmtNum(cur.sb)} / ${fmtNum(cur.bb)}`;
            document.getElementById('fpcAnte').textContent = cur.type === 'break' ? '-' : fmtNum(cur.ante);

            if (!nxt) {
                document.getElementById('fpcNextBlinds').textContent = '無下一級';
                document.getElementById('fpcNextAnte').textContent = '-';
            } else if (nxt.type === 'break') {
                document.getElementById('fpcNextBlinds').textContent = '休息時間';
                document.getElementById('fpcNextAnte').textContent = Math.round(nxt.duration / 60) + 'm';
            } else {
                document.getElementById('fpcNextBlinds').textContent = `${fmtNum(nxt.sb)} / ${fmtNum(nxt.bb)}`;
                document.getElementById('fpcNextAnte').textContent = fmtNum(nxt.ante);
            }

            document.getElementById('btnPlay').textContent = t.status === 'running' ? '⏸' : '▶';
            document.getElementById('btnSound').textContent = t.sound_enabled ? '🔊' : '🔇';
            document.getElementById('fpcPlayers').textContent = `${t.current_players} / ${t.total_entries}`;
            document.getElementById('fpcAvg').textContent = `${fmtNum(c.avg_chips)} (${c.avg_bb}bb)`;
            document.getElementById('fpcPrize').textContent = fmtNum(c.prize_pool);
            document.getElementById('fpcBreak').textContent = c.time_to_break !== null ? fmtTime(c.time_to_break) : '--:--';
        }

        document.getElementById('btnPlay').onclick = () => apiCall({ action: 'toggle_play', tournament_id: tourneyId });
        document.getElementById('fpcTimer').onclick = () => apiCall({ action: 'toggle_play', tournament_id: tourneyId });
        document.getElementById('btnPrev').onclick = () => apiCall({ action: 'change_level', tournament_id: tourneyId, delta: -1 });
        document.getElementById('btnNext').onclick = () => apiCall({ action: 'change_level', tournament_id: tourneyId, delta: 1 });
        document.getElementById('btnSound').onclick = () => apiCall({ action: 'toggle_sound', tournament_id: tourneyId });
        document.getElementById('switchTourney').onchange = (e) => { window.location.href = 'freeclock.php?id=' + encodeURIComponent(e.target.value); };
        document.getElementById('btnFullscreen').onclick = () => {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen().catch(()=>{});
            else document.exitFullscreen().catch(()=>{});
        };

        setInterval(() => {
            const t = state.tournaments[tourneyId];
            if (t && t.status === 'running' && t.remaining_seconds > 0) {
                t.remaining_seconds--;
                if (t.computed && t.computed.time_to_break > 0) t.computed.time_to_break--;
                const dur = (t.computed && t.computed.current_level && t.computed.current_level.duration) || 900;
                if (t.computed) t.computed.progress_percent = (t.remaining_seconds / dur) * 100;
                render();
            }
        }, 1000);

        setInterval(async () => {
            const res = await fetch('api.php?action=state&_t=' + Date.now(), { cache: 'no-store' });
            const json = await res.json();
            if (json && json.ok) { state = json.data; render(); }
        }, 2000);

        render();
    </script>
</body>
</html>
