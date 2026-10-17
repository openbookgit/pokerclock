/**
 * Poker Clock Display Engine (Single & Dual TV Views)
 * Works seamlessly on both NAS (PHP api.php) and GitHub Pages (local-backend.js)
 */

(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const isDual = !!window.DUAL_MODE;
    const isHtmlPage = window.location.pathname.endsWith('.html') || window.location.hostname.endsWith('github.io') || window.location.protocol === 'file:';
    const pageExt = isHtmlPage ? '.html' : '.php';

    let state = window.INITIAL_STATE || (window.PokerLocalStore ? window.PokerLocalStore.getState() : {});
    let tourneyId = urlParams.get('id') || window.INITIAL_TOURNEY_ID || 'tourney_1';
    let dualLeftId = urlParams.get('left') || window.DUAL_LEFT_ID || 'tourney_1';
    let dualRightId = urlParams.get('right') || window.DUAL_RIGHT_ID || 'tourney_2';

    // Track previous values for sound triggers
    let lastLevelIndex = {};
    let lastBeepSecond = {};
    let localOverrideTargetMode = null;

    // Announcement slide rotation state
    let annSlideIndex = 0;
    let lastAnnSwitchTime = Date.now();

    function formatNumber(num) {
        const n = Number(num) || 0;
        return n.toLocaleString('en-US');
    }

    function formatClock(seconds) {
        if (seconds === null || seconds === undefined || isNaN(seconds) || seconds < 0) {
            return '--:--';
        }
        const s = Math.max(0, Math.floor(seconds));
        const mins = Math.floor(s / 60);
        const secs = s % 60;
        const mm = mins < 10 ? '0' + mins : String(mins);
        const ss = secs < 10 ? '0' + secs : String(secs);
        return `${mm}:${ss}`;
    }

    function padTwo(n) {
        const num = Number(n);
        if (isNaN(num)) return n;
        return num < 10 ? '0' + num : String(num);
    }

    async function sendAction(payload) {
        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json && json.ok && json.data) {
                state = json.data;
                renderAll();
            }
        } catch (err) {
            console.error('Action failed:', err);
        }
    }

    async function fetchState() {
        try {
            const res = await fetch('api.php?action=state&_t=' + Date.now(), { cache: 'no-store' });
            const json = await res.json();
            if (json && json.ok && json.data) {
                state = json.data;
                renderAll();
            }
        } catch (err) {
            console.warn('Sync poll failed, continuing local countdown:', err);
        }
    }

    function checkAudioCues(t, allowSound) {
        if (!t) return;
        const tid = t.id;
        const idx = t.current_level_index;
        const rem = t.remaining_seconds;

        if (lastLevelIndex[tid] !== undefined && lastLevelIndex[tid] !== idx) {
            if (allowSound) {
                PokerAudio.playLevelUp();
                if (state.settings && state.settings.voice_enabled) {
                    const cur = t.computed && t.computed.current_level;
                    if (cur) {
                        if (cur.type === 'break') {
                            PokerAudio.speak(`${t.title}，休息時間開始`);
                        } else {
                            PokerAudio.speak(`${t.title}，盲注級別 ${cur.level}，小盲 ${cur.sb}，大盲 ${cur.bb}`);
                        }
                    }
                }
            }
        }
        lastLevelIndex[tid] = idx;

        if (t.status === 'running' && allowSound) {
            if (lastBeepSecond[tid] !== rem) {
                lastBeepSecond[tid] = rem;
                if (rem === 60) {
                    PokerAudio.playWarning();
                } else if (rem <= 5 && rem >= 1) {
                    PokerAudio.playTick();
                }
            }
        }
    }

    function getStatusText(status) {
        if (status === 'running') return '進行中';
        if (status === 'finished') return '已結束';
        return '暫停中';
    }

    function populateSelectIfNeeded(selectEl, selectedVal) {
        if (!selectEl || !state || !state.tournaments) return;
        const keys = Object.keys(state.tournaments);
        if (selectEl.options.length !== keys.length) {
            selectEl.innerHTML = '';
            keys.forEach(tid => {
                const opt = new Option(state.tournaments[tid].title, tid, false, tid === selectedVal);
                selectEl.add(opt);
            });
        } else {
            Array.from(selectEl.options).forEach((opt, i) => {
                const tid = keys[i];
                if (tid && state.tournaments[tid]) {
                    opt.value = tid;
                    opt.textContent = state.tournaments[tid].title;
                    opt.selected = (tid === selectedVal);
                }
            });
        }
    }

    function renderSingleView() {
        if (!state || !state.tournaments) return;
        const t = state.tournaments[tourneyId] || Object.values(state.tournaments)[0];
        if (!t) return;
        tourneyId = t.id;

        const settings = state.settings || {};
        const comp = t.computed || {};
        const curLvl = comp.current_level || { type: 'level', level: 1, sb: 0, bb: 0, ante: 0, duration: 900 };
        const nextLvl = comp.next_level || null;

        checkAudioCues(t, !!t.sound_enabled);

        populateSelectIfNeeded(document.getElementById('ctrlSwitchTourney'), tourneyId);

        const body = document.getElementById('tvBody');
        body.className = 'tv-body theme-' + (t.theme || 'gold') + (body.classList.contains('show-controls') ? ' show-controls' : '');
        FireEffect.setTheme(t.theme || 'gold');
        FireEffect.setEnabled(settings.fire_effect !== false);

        const fireBtn = document.getElementById('btnToggleFire');
        if (fireBtn) {
            fireBtn.textContent = '火焰 : ' + (settings.fire_effect !== false ? '開' : '關');
        }

        const brandIcon = document.getElementById('brandIcon');
        const brandName = document.getElementById('brandName');
        if (brandIcon) brandIcon.textContent = settings.brand_logo_icon || '♠';
        if (brandName) brandName.textContent = settings.brand_name || 'POKER CLOCK';

        document.title = `${t.title} - ${settings.brand_name || 'POKER CLOCK'}`;
        document.getElementById('tourneyTitle').textContent = t.title || '';
        document.getElementById('tourneySubtitle').textContent = t.subtitle || '';

        const soundBtn = document.getElementById('btnToggleSound');
        const soundIcon = document.getElementById('soundIcon');
        const soundText = document.getElementById('soundText');
        if (soundBtn) {
            soundBtn.classList.toggle('muted', !t.sound_enabled);
            soundIcon.textContent = t.sound_enabled ? '🔊' : '🔇';
            soundText.textContent = t.sound_enabled ? '音效開' : '音效關';
        }

        const anns = Array.isArray(t.announcements) && t.announcements.length > 0
            ? t.announcements
            : [{ title: '賽事規則', content: '歡迎參賽' }];
        const intervalMs = Math.max(3, Number(settings.announcement_interval || 8)) * 1000;
        if (Date.now() - lastAnnSwitchTime >= intervalMs) {
            annSlideIndex = (annSlideIndex + 1) % anns.length;
            lastAnnSwitchTime = Date.now();
        }
        if (annSlideIndex >= anns.length) annSlideIndex = 0;

        const currentAnn = anns[annSlideIndex];
        const pageInd = document.getElementById('annPageIndicator');
        if (pageInd) {
            pageInd.textContent = anns.length > 1 ? `${annSlideIndex + 1}/${anns.length}` : '';
        }
        document.getElementById('annSlideTitle').textContent = currentAnn.title || '';
        document.getElementById('annSlideContent').textContent = currentAnn.content || '';

        const annFooter = document.getElementById('annFooter');
        if (annFooter) {
            if (t.footer_notice && t.footer_notice.trim() !== '') {
                annFooter.style.display = 'block';
                annFooter.textContent = t.footer_notice;
            } else {
                annFooter.style.display = 'none';
            }
        }

        const levelLabel = document.getElementById('levelLabel');
        if (curLvl.type === 'break') {
            levelLabel.textContent = '休息時間 Break';
        } else {
            levelLabel.textContent = 'Level ' + curLvl.level;
        }

        const statusText = getStatusText(t.status);
        const statusLabel = document.getElementById('statusLabel');
        statusLabel.textContent = statusText;
        statusLabel.classList.toggle('paused', t.status !== 'running');

        const clockSubLabel = document.getElementById('clockSubLabel');
        if (t.status === 'paused') {
            clockSubLabel.textContent = '暫停中';
        } else if (curLvl.type === 'break') {
            clockSubLabel.textContent = '休息剩餘時間';
        } else {
            clockSubLabel.textContent = '本級剩餘時間';
        }

        const mainTimer = document.getElementById('mainTimer');
        mainTimer.textContent = formatClock(t.remaining_seconds);
        mainTimer.classList.toggle('warning-pulse', t.status === 'running' && t.remaining_seconds <= 60 && t.remaining_seconds > 0);

        const playBtn = document.getElementById('ctrlPlayPause');
        if (playBtn) {
            playBtn.textContent = t.status === 'running' ? '⏸' : '▶';
        }

        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = (comp.progress_percent ?? 100) + '%';
        }

        if (curLvl.type === 'break') {
            document.getElementById('sbValue').textContent = '休息';
            document.getElementById('bbValue').textContent = '時間';
            document.getElementById('anteValue').textContent = '-';
        } else {
            document.getElementById('sbValue').textContent = formatNumber(curLvl.sb);
            document.getElementById('bbValue').textContent = formatNumber(curLvl.bb);
            document.getElementById('anteValue').textContent = formatNumber(curLvl.ante);
        }

        const nextLevelEl = document.getElementById('nextLevelValue');
        if (!nextLvl) {
            nextLevelEl.textContent = '無下一級';
        } else if (nextLvl.type === 'break') {
            nextLevelEl.textContent = `休息 (${Math.round(nextLvl.duration / 60)}分鐘)`;
        } else {
            nextLevelEl.textContent = `${formatNumber(nextLvl.sb)} / ${formatNumber(nextLvl.bb)}`;
        }

        const mode = localOverrideTargetMode || t.right_top_mode || 'auto';
        const hasBreak = comp.time_to_break !== null && comp.time_to_break !== undefined && curLvl.type !== 'break';
        const targetHeader = document.getElementById('targetTimeHeader');
        const targetValue = document.getElementById('targetTimeValue');

        if (mode === 'break' || (mode === 'auto' && hasBreak)) {
            targetHeader.textContent = '距離休息';
            targetValue.textContent = hasBreak ? formatClock(comp.time_to_break) : '--:--';
        } else {
            targetHeader.textContent = '距離賽事結束';
            targetValue.textContent = formatClock(comp.time_to_end);
        }

        document.getElementById('avgStackValue').textContent = `${formatNumber(comp.avg_chips)}(${comp.avg_bb}bb)`;
        document.getElementById('prizePoolValue').textContent = formatNumber(comp.prize_pool);
        document.getElementById('playersRatioValue').textContent = `${t.current_players} / ${t.total_entries}`;
    }

    function renderDualSide(side, t, dualMuted) {
        if (!t) return;
        const comp = t.computed || {};
        const curLvl = comp.current_level || { type: 'level', level: 1, sb: 0, bb: 0, ante: 0, duration: 900 };

        checkAudioCues(t, !dualMuted && !!t.sound_enabled);

        const card = document.getElementById('dualCard_' + side);
        if (card) {
            card.className = 'dual-card theme-' + (t.theme || 'gold');
        }

        document.getElementById(side + '_title').textContent = t.title || '';
        document.getElementById(side + '_subtitle').textContent = t.subtitle || '';
        document.getElementById(side + '_status').textContent = getStatusText(t.status);
        document.getElementById(side + '_timer').textContent = formatClock(t.remaining_seconds);

        const prog = document.getElementById(side + '_progress');
        if (prog) {
            prog.style.width = (comp.progress_percent ?? 100) + '%';
        }

        document.getElementById(side + '_timeToEnd').textContent = formatClock(comp.time_to_end);
        const hasBreak = comp.time_to_break !== null && comp.time_to_break !== undefined && curLvl.type !== 'break';
        document.getElementById(side + '_timeToBreak').textContent = hasBreak ? formatClock(comp.time_to_break) : '--:--';

        const lvlTag = document.getElementById(side + '_levelTag');
        const blindsVal = document.getElementById(side + '_blinds');
        if (curLvl.type === 'break') {
            lvlTag.textContent = 'Break';
            blindsVal.textContent = '休息時間';
        } else {
            lvlTag.textContent = 'Level ' + padTwo(curLvl.level);
            blindsVal.textContent = `${formatNumber(curLvl.sb)}/${formatNumber(curLvl.bb)}`;
        }

        document.getElementById(side + '_players').textContent = `${t.current_players}/${t.total_entries}`;
        document.getElementById(side + '_avgChips').textContent = formatNumber(comp.avg_chips);
        document.getElementById(side + '_avgBB').textContent = `(${comp.avg_bb}bb)`;
        document.getElementById(side + '_prize').textContent = formatNumber(comp.prize_pool);
    }

    function renderDualView() {
        if (!state || !state.tournaments) return;
        const settings = state.settings || {};
        const keys = Object.keys(state.tournaments);
        const leftId = settings.dual_left_id && state.tournaments[settings.dual_left_id]
            ? settings.dual_left_id
            : (state.tournaments[dualLeftId] ? dualLeftId : keys[0]);
        const rightId = settings.dual_right_id && state.tournaments[settings.dual_right_id]
            ? settings.dual_right_id
            : (state.tournaments[dualRightId] ? dualRightId : keys[Math.min(1, keys.length - 1)]);

        dualLeftId = leftId;
        dualRightId = rightId;

        populateSelectIfNeeded(document.getElementById('dualSelectLeft'), dualLeftId);
        populateSelectIfNeeded(document.getElementById('dualSelectRight'), dualRightId);

        const brandIcon = document.getElementById('brandIcon');
        const brandName = document.getElementById('brandName');
        if (brandIcon) brandIcon.textContent = settings.brand_logo_icon || '♠';
        if (brandName) brandName.textContent = settings.brand_name || 'POKER CLOCK';

        const dualMuted = settings.dual_muted !== false;
        const dualSoundStatus = document.getElementById('dualSoundStatus');
        if (dualSoundStatus) {
            dualSoundStatus.textContent = dualMuted ? '音效全靜音' : '音效已開啟';
        }

        FireEffect.setTheme('dual');
        FireEffect.setEnabled(settings.fire_effect !== false);
        const fireBtn = document.getElementById('btnToggleFire');
        if (fireBtn) {
            fireBtn.textContent = '火焰 : ' + (settings.fire_effect !== false ? '開' : '關');
        }

        renderDualSide('left', state.tournaments[leftId], dualMuted);
        renderDualSide('right', state.tournaments[rightId], dualMuted);
    }

    function renderAll() {
        if (isDual) {
            renderDualView();
        } else {
            renderSingleView();
        }
    }

    function localTick() {
        if (!state || !state.tournaments) return;
        let needServerSync = false;

        Object.values(state.tournaments).forEach(t => {
            if (t.status === 'running') {
                if (t.remaining_seconds > 0) {
                    t.remaining_seconds--;
                    if (t.computed) {
                        if (t.computed.time_to_end > 0) t.computed.time_to_end--;
                        if (t.computed.time_to_break !== null && t.computed.time_to_break > 0) {
                            t.computed.time_to_break--;
                        }
                        const dur = (t.computed.current_level && t.computed.current_level.duration) || 900;
                        t.computed.progress_percent = Math.min(100, Math.max(0, (t.remaining_seconds / dur) * 100));
                    }
                }
                if (t.remaining_seconds <= 0) {
                    needServerSync = true;
                }
            }
        });

        renderAll();
        if (needServerSync) {
            fetchState();
        }
    }

    let hideTimeout = null;
    function showFloatingControls() {
        document.body.classList.add('show-controls');
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            document.body.classList.remove('show-controls');
        }, 3200);
    }

    window.addEventListener('mousemove', showFloatingControls);
    window.addEventListener('touchstart', showFloatingControls);

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen().catch(() => {});
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.PokerLocalStore) {
            window.PokerLocalStore.onSync((newState) => {
                state = newState;
                renderAll();
            });
        }

        const initTheme = isDual ? 'dual' : ((state.tournaments && state.tournaments[tourneyId] && state.tournaments[tourneyId].theme) || 'gold');
        FireEffect.init('fireCanvas', initTheme);
        fetchState();

        const btnFire = document.getElementById('btnToggleFire');
        if (btnFire) {
            btnFire.addEventListener('click', () => sendAction({ action: 'toggle_fire' }));
        }

        const btnFs = document.getElementById('ctrlFullscreen');
        if (btnFs) {
            btnFs.addEventListener('click', toggleFullscreen);
        }

        if (!isDual) {
            document.getElementById('btnToggleSound').addEventListener('click', () => {
                sendAction({ action: 'toggle_sound', tournament_id: tourneyId });
            });

            document.getElementById('mainTimer').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: tourneyId });
            });

            document.getElementById('ctrlPlayPause').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: tourneyId });
            });

            document.getElementById('ctrlPrevLvl').addEventListener('click', () => {
                sendAction({ action: 'change_level', tournament_id: tourneyId, delta: -1 });
            });

            document.getElementById('ctrlNextLvl').addEventListener('click', () => {
                sendAction({ action: 'change_level', tournament_id: tourneyId, delta: 1 });
            });

            document.getElementById('ctrlMinusMin').addEventListener('click', () => {
                sendAction({ action: 'adjust_time', tournament_id: tourneyId, delta: -60 });
            });

            document.getElementById('ctrlPlusMin').addEventListener('click', () => {
                sendAction({ action: 'adjust_time', tournament_id: tourneyId, delta: 60 });
            });

            document.getElementById('ctrlBuyin').addEventListener('click', () => {
                sendAction({ action: 'adjust_players', tournament_id: tourneyId, sub_action: 'buyin' });
            });

            document.getElementById('ctrlBust').addEventListener('click', () => {
                sendAction({ action: 'adjust_players', tournament_id: tourneyId, sub_action: 'bust' });
            });

            document.getElementById('targetTimeCard').addEventListener('click', () => {
                const curHeader = document.getElementById('targetTimeHeader').textContent;
                localOverrideTargetMode = (curHeader === '距離休息') ? 'end' : 'break';
                renderSingleView();
            });

            const switchSelect = document.getElementById('ctrlSwitchTourney');
            if (switchSelect) {
                switchSelect.addEventListener('change', (e) => {
                    window.location.href = 'display' + pageExt + '?id=' + encodeURIComponent(e.target.value);
                });
            }

            window.addEventListener('keydown', (e) => {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
                if (e.code === 'Space') {
                    e.preventDefault();
                    sendAction({ action: 'toggle_play', tournament_id: tourneyId });
                } else if (e.code === 'ArrowLeft') {
                    e.preventDefault();
                    sendAction({ action: 'change_level', tournament_id: tourneyId, delta: -1 });
                } else if (e.code === 'ArrowRight') {
                    e.preventDefault();
                    sendAction({ action: 'change_level', tournament_id: tourneyId, delta: 1 });
                } else if (e.code === 'ArrowUp') {
                    e.preventDefault();
                    sendAction({ action: 'adjust_players', tournament_id: tourneyId, sub_action: 'buyin' });
                } else if (e.code === 'ArrowDown') {
                    e.preventDefault();
                    sendAction({ action: 'adjust_players', tournament_id: tourneyId, sub_action: 'bust' });
                } else if (e.code === 'KeyF') {
                    e.preventDefault();
                    toggleFullscreen();
                } else if (e.code === 'KeyM') {
                    e.preventDefault();
                    sendAction({ action: 'toggle_sound', tournament_id: tourneyId });
                }
            });
        } else {
            document.getElementById('btnDualSound').addEventListener('click', () => {
                sendAction({ action: 'toggle_sound', dual: true });
            });

            document.getElementById('left_timer').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: dualLeftId });
            });

            document.getElementById('right_timer').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: dualRightId });
            });

            document.getElementById('btnLeftPlay').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: dualLeftId });
            });

            document.getElementById('btnRightPlay').addEventListener('click', () => {
                sendAction({ action: 'toggle_play', tournament_id: dualRightId });
            });

            const selLeft = document.getElementById('dualSelectLeft');
            const selRight = document.getElementById('dualSelectRight');
            if (selLeft) {
                selLeft.addEventListener('change', (e) => {
                    dualLeftId = e.target.value;
                    sendAction({ action: 'save_settings', settings: { dual_left_id: dualLeftId } });
                });
            }
            if (selRight) {
                selRight.addEventListener('change', (e) => {
                    dualRightId = e.target.value;
                    sendAction({ action: 'save_settings', settings: { dual_right_id: dualRightId } });
                });
            }

            window.addEventListener('keydown', (e) => {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
                if (e.code === 'KeyF') {
                    e.preventDefault();
                    toggleFullscreen();
                }
            });
        }

        setInterval(localTick, 1000);
        setInterval(fetchState, 2000);
    });
})();
