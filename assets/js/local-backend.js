/**
 * Universal Backend Adapter for Poker Clock
 * - On NAS (PHP enabled): Uses server-side api.php for multi-device LAN sync
 * - On GitHub Pages (Static hosting): Automatically falls back to localStorage + BroadcastChannel
 *   so the entire app (index, display, dual, freeclock, admin) runs 100% on GitHub Pages!
 */

(function () {
    const STORAGE_KEY = 'pokerclock_state_v1';
    const bc = ('BroadcastChannel' in window) ? new BroadcastChannel('pokerclock_channel') : null;
    let phpAvailable = null; // null = unknown, true = NAS PHP mode, false = GitHub Pages Static mode

    function getStandard18Levels() {
        const structure = [
            { sb: 100, bb: 100, ante: 100, duration: 900 },
            { sb: 100, bb: 200, ante: 200, duration: 900 },
            { sb: 100, bb: 300, ante: 300, duration: 900 },
            { sb: 200, bb: 400, ante: 400, duration: 900 },
            { sb: 300, bb: 600, ante: 600, duration: 900 },
            { sb: 400, bb: 800, ante: 800, duration: 900 },
            { sb: 500, bb: 1000, ante: 1000, duration: 900 },
            { sb: 600, bb: 1200, ante: 1200, duration: 900 },
            { sb: 800, bb: 1600, ante: 1600, duration: 900 },
            { sb: 1000, bb: 2000, ante: 2000, duration: 900 },
            { sb: 1000, bb: 2500, ante: 2500, duration: 900 },
            { sb: 1500, bb: 3000, ante: 3000, duration: 900 },
            { sb: 2000, bb: 4000, ante: 4000, duration: 900 },
            { sb: 2500, bb: 5000, ante: 5000, duration: 900 },
            { sb: 3000, bb: 6000, ante: 6000, duration: 900 },
            { sb: 3500, bb: 7000, ante: 7000, duration: 900 },
            { sb: 4000, bb: 8000, ante: 8000, duration: 900 },
            { sb: 5000, bb: 10000, ante: 10000, duration: 900 }
        ];
        const levels = structure.map((item, idx) => ({
            type: 'level',
            level: idx + 1,
            sb: item.sb,
            bb: item.bb,
            ante: item.ante,
            duration: item.duration
        }));
        levels.push({ type: 'break', level: '休息', sb: 0, bb: 0, ante: 0, duration: 600 });
        const more = [
            { sb: 6000, bb: 12000, ante: 12000, duration: 1800 },
            { sb: 8000, bb: 16000, ante: 16000, duration: 1800 },
            { sb: 10000, bb: 20000, ante: 20000, duration: 1800 },
            { sb: 15000, bb: 30000, ante: 30000, duration: 1800 },
            { sb: 20000, bb: 40000, ante: 40000, duration: 1800 },
            { sb: 25000, bb: 50000, ante: 50000, duration: 1800 },
            { sb: 30000, bb: 60000, ante: 60000, duration: 1800 },
            { sb: 40000, bb: 80000, ante: 80000, duration: 1800 },
            { sb: 50000, bb: 100000, ante: 100000, duration: 2100 },
            { sb: 75000, bb: 150000, ante: 150000, duration: 2100 }
        ];
        let lvlNum = 19;
        more.forEach(item => {
            levels.push({
                type: 'level',
                level: lvlNum++,
                sb: item.sb,
                bb: item.bb,
                ante: item.ante,
                duration: item.duration
            });
        });
        return levels;
    }

    function getDefaultState() {
        const now = Math.floor(Date.now() / 1000);
        return {
            settings: {
                brand_name: 'POKER DOMAIN',
                brand_logo_icon: '♠',
                fire_effect: true,
                dual_left_id: 'tourney_1',
                dual_right_id: 'tourney_2',
                dual_muted: true,
                announcement_interval: 8,
                voice_enabled: false
            },
            tournaments: {
                tourney_1: {
                    id: 'tourney_1',
                    title: '3400限時錦標賽_1',
                    subtitle: '撲克王積分榜倒數1天',
                    theme: 'gold',
                    status: 'paused',
                    current_level_index: 1,
                    remaining_seconds: 3521,
                    last_updated_at: now,
                    sound_enabled: true,
                    right_top_mode: 'auto',
                    current_players: 18,
                    total_entries: 20,
                    starting_chips: 500000,
                    rebuy_count: 0,
                    rebuy_chips: 500000,
                    addon_count: 0,
                    addon_chips: 500000,
                    manual_total_chips: 10000000,
                    buyin_prize: 3000,
                    manual_prize_pool: 60000,
                    announcements: [
                        { title: '賽事規則', content: 'Rebuy優於候補\n限時錦標賽時間到立即結算計分牌' },
                        { title: '賽事規則', content: 'Rebuy優於候補' }
                    ],
                    footer_notice: '',
                    levels: [
                        { type: 'level', level: 1, sb: 1000, bb: 3000, ante: 0, duration: 1800 },
                        { type: 'level', level: 2, sb: 2000, bb: 4000, ante: 0, duration: 3600 }
                    ]
                },
                tourney_2: {
                    id: 'tourney_2',
                    title: 'iPhone 18 pro搶先賽',
                    subtitle: 'DAILY TOURNAMENT • 每日錦標賽',
                    theme: 'cyan',
                    status: 'paused',
                    current_level_index: 16,
                    remaining_seconds: 521,
                    last_updated_at: now,
                    sound_enabled: true,
                    right_top_mode: 'break',
                    current_players: 10,
                    total_entries: 61,
                    starting_chips: 30000,
                    rebuy_count: 0,
                    rebuy_chips: 30000,
                    addon_count: 0,
                    addon_chips: 30000,
                    manual_total_chips: 1830000,
                    buyin_prize: 650,
                    manual_prize_pool: 39650,
                    announcements: [
                        { title: '獎池分配', content: '1th iPhone 18 pro\n2nd 8,000\n3rd 5,000' }
                    ],
                    footer_notice: '折扣額由領域補貼獎池',
                    levels: getStandard18Levels()
                }
            }
        };
    }

    function enrichState(state) {
        const copy = JSON.parse(JSON.stringify(state));
        copy.server_time = Math.floor(Date.now() / 1000);

        Object.values(copy.tournaments || {}).forEach(t => {
            const levels = t.levels || [];
            let idx = parseInt(t.current_level_index || 0, 10);
            if (idx < 0) idx = 0;
            if (idx >= levels.length && levels.length > 0) idx = levels.length - 1;
            t.current_level_index = idx;

            const currentLevel = levels[idx] || { type: 'level', level: 1, sb: 100, bb: 200, ante: 0, duration: 900 };
            const nextLevel = levels[idx + 1] || null;
            const rem = Math.max(0, parseInt(t.remaining_seconds || 0, 10));
            const duration = Math.max(1, parseInt(currentLevel.duration || 900, 10));

            let timeToBreak = null;
            let timeToEnd = rem;
            for (let i = idx + 1; i < levels.length; i++) {
                timeToEnd += Math.max(0, parseInt(levels[i].duration || 0, 10));
            }

            if (currentLevel.type === 'break') {
                timeToBreak = 0;
            } else {
                let accBreak = rem;
                let foundBreak = false;
                for (let i = idx + 1; i < levels.length; i++) {
                    if (levels[i].type === 'break') {
                        foundBreak = true;
                        break;
                    }
                    accBreak += Math.max(0, parseInt(levels[i].duration || 0, 10));
                }
                if (foundBreak) timeToBreak = accBreak;
            }

            const entries = Math.max(0, parseInt(t.total_entries || 0, 10));
            const currentPlayers = Math.max(0, parseInt(t.current_players || 0, 10));
            const startingChips = Math.max(0, parseInt(t.starting_chips || 0, 10));
            const rebuyCount = Math.max(0, parseInt(t.rebuy_count || 0, 10));
            const rebuyChips = Math.max(0, parseInt(t.rebuy_chips || 0, 10));
            const addonCount = Math.max(0, parseInt(t.addon_count || 0, 10));
            const addonChips = Math.max(0, parseInt(t.addon_chips || 0, 10));

            let totalChips = parseInt(t.manual_total_chips || 0, 10);
            if (totalChips <= 0) {
                totalChips = (entries * startingChips) + (rebuyCount * rebuyChips) + (addonCount * addonChips);
            }

            const avgChips = currentPlayers > 0 ? Math.round(totalChips / currentPlayers) : 0;
            const currentBB = parseInt(currentLevel.bb || 0, 10);
            const avgBB = (currentBB > 0 && avgChips > 0) ? Math.round((avgChips / currentBB) * 10) / 10 : 0;

            let prizePool = parseInt(t.manual_prize_pool || 0, 10);
            if (prizePool <= 0) {
                const buyinPrize = Math.max(0, parseInt(t.buyin_prize || 0, 10));
                prizePool = (entries + rebuyCount + addonCount) * buyinPrize;
            }

            const progressPercent = Math.min(100, Math.max(0, Math.round((rem / duration) * 10000) / 100));

            t.computed = {
                current_level: currentLevel,
                next_level: nextLevel,
                time_to_break: timeToBreak,
                time_to_end: timeToEnd,
                total_chips: totalChips,
                avg_chips: avgChips,
                avg_bb: avgBB,
                prize_pool: prizePool,
                progress_percent: progressPercent
            };
        });

        return copy;
    }

    function loadLocalState() {
        let state = null;
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) state = JSON.parse(raw);
        } catch (e) {}

        if (!state || !state.tournaments) {
            state = getDefaultState();
            saveLocalState(state);
        }

        const now = Math.floor(Date.now() / 1000);
        let dirty = false;

        Object.values(state.tournaments).forEach(t => {
            if (t.status === 'running') {
                const elapsed = now - (t.last_updated_at || now);
                if (elapsed > 0) {
                    dirty = true;
                    t.last_updated_at = now;
                    let rem = (t.remaining_seconds || 0) - elapsed;
                    while (rem <= 0 && t.status === 'running') {
                        const nextIndex = t.current_level_index + 1;
                        if (t.levels && t.levels[nextIndex]) {
                            t.current_level_index = nextIndex;
                            rem += Math.max(1, parseInt(t.levels[nextIndex].duration || 900, 10));
                        } else {
                            rem = 0;
                            t.status = 'finished';
                            break;
                        }
                    }
                    t.remaining_seconds = Math.max(0, rem);
                }
            } else {
                t.last_updated_at = now;
            }
        });

        if (dirty) saveLocalState(state);
        return enrichState(state);
    }

    function saveLocalState(state) {
        const clean = JSON.parse(JSON.stringify(state));
        delete clean.server_time;
        Object.values(clean.tournaments || {}).forEach(t => delete t.computed);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(clean));
            if (bc) bc.postMessage({ type: 'state_updated' });
        } catch (e) {}
    }

    function handleLocalAction(input) {
        const state = loadLocalState();
        const now = Math.floor(Date.now() / 1000);
        const action = input.action || 'state';
        const tid = input.tournament_id || '';

        if (action === 'toggle_play' && state.tournaments[tid]) {
            const t = state.tournaments[tid];
            if (t.status === 'running') {
                t.status = 'paused';
            } else {
                if (t.remaining_seconds <= 0) {
                    t.remaining_seconds = parseInt((t.levels[t.current_level_index] || {}).duration || 900, 10);
                }
                t.status = 'running';
            }
            t.last_updated_at = now;
            saveLocalState(state);
        } else if (action === 'change_level' && state.tournaments[tid]) {
            const t = state.tournaments[tid];
            const count = (t.levels || []).length;
            if (count > 0) {
                let newIdx = (input.level_index !== undefined)
                    ? parseInt(input.level_index, 10)
                    : parseInt(t.current_level_index, 10) + parseInt(input.delta || 0, 10);
                newIdx = Math.max(0, Math.min(count - 1, newIdx));
                t.current_level_index = newIdx;
                t.remaining_seconds = Math.max(1, parseInt(t.levels[newIdx].duration || 900, 10));
                t.last_updated_at = now;
                if (t.status === 'finished') t.status = 'paused';
                saveLocalState(state);
            }
        } else if (action === 'adjust_time' && state.tournaments[tid]) {
            const t = state.tournaments[tid];
            if (input.reset) {
                t.remaining_seconds = Math.max(1, parseInt((t.levels[t.current_level_index] || {}).duration || 900, 10));
            } else if (input.set_seconds !== undefined) {
                t.remaining_seconds = Math.max(0, parseInt(input.set_seconds, 10));
            } else {
                t.remaining_seconds = Math.max(0, parseInt(t.remaining_seconds, 10) + parseInt(input.delta || 0, 10));
            }
            t.last_updated_at = now;
            saveLocalState(state);
        } else if (action === 'adjust_players' && state.tournaments[tid]) {
            const t = state.tournaments[tid];
            const sub = input.sub_action || '';
            if (sub === 'buyin') {
                t.current_players = Math.max(0, parseInt(t.current_players, 10) + 1);
                t.total_entries = Math.max(t.current_players, parseInt(t.total_entries, 10) + 1);
            } else if (sub === 'bust') {
                t.current_players = Math.max(0, parseInt(t.current_players, 10) - 1);
            } else if (sub === 'revive') {
                t.current_players = Math.min(parseInt(t.total_entries, 10), parseInt(t.current_players, 10) + 1);
            } else if (sub === 'rebuy') {
                t.rebuy_count = Math.max(0, parseInt(t.rebuy_count || 0, 10) + parseInt(input.delta || 1, 10));
            } else if (sub === 'addon') {
                t.addon_count = Math.max(0, parseInt(t.addon_count || 0, 10) + parseInt(input.delta || 1, 10));
            }
            saveLocalState(state);
        } else if (action === 'toggle_sound') {
            if (tid && state.tournaments[tid]) {
                state.tournaments[tid].sound_enabled = !state.tournaments[tid].sound_enabled;
                saveLocalState(state);
            } else if (input.dual) {
                state.settings.dual_muted = !state.settings.dual_muted;
                saveLocalState(state);
            }
        } else if (action === 'toggle_fire') {
            state.settings.fire_effect = !state.settings.fire_effect;
            saveLocalState(state);
        } else if (action === 'save_tournament' && tid && state.tournaments[tid] && input.tournament) {
            const ex = state.tournaments[tid];
            const td = input.tournament;
            ex.title = (td.title || ex.title).trim();
            ex.subtitle = (td.subtitle ?? ex.subtitle).trim();
            ex.theme = td.theme || ex.theme;
            ex.right_top_mode = td.right_top_mode || ex.right_top_mode || 'auto';
            ex.current_players = Math.max(0, parseInt(td.current_players ?? ex.current_players, 10));
            ex.total_entries = Math.max(0, parseInt(td.total_entries ?? ex.total_entries, 10));
            ex.starting_chips = Math.max(0, parseInt(td.starting_chips ?? ex.starting_chips, 10));
            ex.rebuy_count = Math.max(0, parseInt(td.rebuy_count ?? ex.rebuy_count, 10));
            ex.rebuy_chips = Math.max(0, parseInt(td.rebuy_chips ?? ex.rebuy_chips, 10));
            ex.addon_count = Math.max(0, parseInt(td.addon_count ?? ex.addon_count, 10));
            ex.addon_chips = Math.max(0, parseInt(td.addon_chips ?? ex.addon_chips, 10));
            ex.manual_total_chips = Math.max(0, parseInt(td.manual_total_chips ?? 0, 10));
            ex.buyin_prize = Math.max(0, parseInt(td.buyin_prize ?? ex.buyin_prize, 10));
            ex.manual_prize_pool = Math.max(0, parseInt(td.manual_prize_pool ?? 0, 10));
            ex.footer_notice = (td.footer_notice ?? '').trim();

            if (Array.isArray(td.announcements)) {
                ex.announcements = td.announcements
                    .filter(a => (a.title || '').trim() !== '' || (a.content || '').trim() !== '')
                    .map(a => ({ title: (a.title || '賽事公告').trim(), content: (a.content || '').trim() }));
            }
            if (Array.isArray(td.levels) && td.levels.length > 0) {
                let c = 1;
                ex.levels = td.levels.map(l => {
                    const tp = l.type === 'break' ? 'break' : 'level';
                    return {
                        type: tp,
                        level: tp === 'break' ? '休息' : c++,
                        sb: tp === 'break' ? 0 : Math.max(0, parseInt(l.sb || 0, 10)),
                        bb: tp === 'break' ? 0 : Math.max(0, parseInt(l.bb || 0, 10)),
                        ante: tp === 'break' ? 0 : Math.max(0, parseInt(l.ante || 0, 10)),
                        duration: Math.max(10, parseInt(l.duration || 900, 10))
                    };
                });
                if (ex.current_level_index >= ex.levels.length) {
                    ex.current_level_index = ex.levels.length - 1;
                    ex.remaining_seconds = ex.levels[ex.current_level_index].duration;
                }
            }
            saveLocalState(state);
        } else if (action === 'create_tournament') {
            const newId = 'tourney_' + Date.now();
            const tpl = input.template || 'standard';
            const dur = tpl === 'turbo' ? 600 : 1200;
            const levels = [
                { type: 'level', level: 1, sb: 100, bb: 200, ante: 200, duration: dur },
                { type: 'level', level: 2, sb: 200, bb: 400, ante: 400, duration: dur },
                { type: 'level', level: 3, sb: 300, bb: 600, ante: 600, duration: dur },
                { type: 'level', level: 4, sb: 400, bb: 800, ante: 800, duration: dur },
                { type: 'break', level: '休息', sb: 0, bb: 0, ante: 0, duration: 600 },
                { type: 'level', level: 5, sb: 500, bb: 1000, ante: 1000, duration: dur },
                { type: 'level', level: 6, sb: 600, bb: 1200, ante: 1200, duration: dur },
                { type: 'level', level: 7, sb: 800, bb: 1600, ante: 1600, duration: dur },
                { type: 'level', level: 8, sb: 1000, bb: 2000, ante: 2000, duration: dur }
            ];
            state.tournaments[newId] = {
                id: newId,
                title: (input.title || '新錦標賽').trim(),
                subtitle: 'DAILY TOURNAMENT • 錦標賽',
                theme: input.theme || 'gold',
                status: 'paused',
                current_level_index: 0,
                remaining_seconds: levels[0].duration,
                last_updated_at: now,
                sound_enabled: true,
                right_top_mode: 'auto',
                current_players: 9,
                total_entries: 9,
                starting_chips: 30000,
                rebuy_count: 0,
                rebuy_chips: 30000,
                addon_count: 0,
                addon_chips: 30000,
                manual_total_chips: 0,
                buyin_prize: 1000,
                manual_prize_pool: 0,
                announcements: [{ title: '賽事規則', content: '歡迎參加本次賽事' }],
                footer_notice: '',
                levels: levels
            };
            saveLocalState(state);
        } else if (action === 'delete_tournament') {
            if (state.tournaments[tid] && Object.keys(state.tournaments).length > 1) {
                delete state.tournaments[tid];
                const keys = Object.keys(state.tournaments);
                if (state.settings.dual_left_id === tid) state.settings.dual_left_id = keys[0];
                if (state.settings.dual_right_id === tid) state.settings.dual_right_id = keys[Math.min(1, keys.length - 1)];
                saveLocalState(state);
            }
        } else if (action === 'save_settings' && input.settings) {
            Object.assign(state.settings, input.settings);
            saveLocalState(state);
        } else if (action === 'reset_demo') {
            const def = getDefaultState();
            saveLocalState(def);
        }

        return { ok: true, data: loadLocalState() };
    }

    // Intercept fetch calls to api.php and fallback to LocalEngine when PHP is not running (e.g. GitHub Pages)
    const origFetch = window.fetch.bind(window);
    window.fetch = async function (resource, init) {
        const url = typeof resource === 'string' ? resource : (resource && resource.url) || '';
        if (url.indexOf('api.php') !== -1) {
            if (phpAvailable === false || window.location.hostname.endsWith('github.io') || window.location.protocol === 'file:') {
                phpAvailable = false;
                let payload = {};
                if (init && init.body) {
                    try { payload = JSON.parse(init.body); } catch (e) {}
                }
                const result = handleLocalAction(payload);
                return new Response(JSON.stringify(result), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                });
            }

            try {
                const res = await origFetch(resource, init);
                const clone = res.clone();
                const text = await clone.text();
                // If server returned raw PHP source code or 404/405 (static server), switch to local mode
                if (!res.ok || text.trim().startsWith('<?php')) {
                    phpAvailable = false;
                    let payload = {};
                    if (init && init.body) {
                        try { payload = JSON.parse(init.body); } catch (e) {}
                    }
                    const result = handleLocalAction(payload);
                    return new Response(JSON.stringify(result), {
                        status: 200,
                        headers: { 'Content-Type': 'application/json' }
                    });
                }
                phpAvailable = true;
                return res;
            } catch (err) {
                phpAvailable = false;
                let payload = {};
                if (init && init.body) {
                    try { payload = JSON.parse(init.body); } catch (e) {}
                }
                const result = handleLocalAction(payload);
                return new Response(JSON.stringify(result), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        }
        return origFetch(resource, init);
    };

    window.PokerLocalStore = {
        getState: loadLocalState,
        onSync: function (callback) {
            if (bc) {
                bc.onmessage = () => callback(loadLocalState());
            }
            window.addEventListener('storage', (e) => {
                if (e.key === STORAGE_KEY) callback(loadLocalState());
            });
        }
    };
})();
