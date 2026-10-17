<?php
/**
 * Poker Clock Storage & State Engine
 * Designed for NAS environments (Synology Web Station / QNAP / Docker)
 * Zero database dependencies - uses JSON file storage with automatic temp fallback
 */

define('DATA_DIR', __DIR__ . '/../data');

/**
 * Resolve writable state file path.
 * Prefers /data/state.json; automatically falls back to system temp dir if /data is not writable on NAS.
 */
function get_state_file_path() {
    static $resolvedPath = null;
    if ($resolvedPath !== null) {
        return $resolvedPath;
    }

    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0777, true);
    }

    $primaryFile = DATA_DIR . '/state.json';

    // Check if primary file is writable or directory is writable
    if ((file_exists($primaryFile) && is_writable($primaryFile)) || (!file_exists($primaryFile) && is_dir(DATA_DIR) && is_writable(DATA_DIR))) {
        $resolvedPath = $primaryFile;
        return $resolvedPath;
    }

    // Try chmod in case PHP owns the directory
    @chmod(DATA_DIR, 0777);
    if (file_exists($primaryFile)) {
        @chmod($primaryFile, 0666);
    }
    if ((file_exists($primaryFile) && is_writable($primaryFile)) || (!file_exists($primaryFile) && is_dir(DATA_DIR) && is_writable(DATA_DIR))) {
        $resolvedPath = $primaryFile;
        return $resolvedPath;
    }

    // Fallback to system temp directory (always writable by Synology http user)
    $fallbackFile = rtrim(sys_get_temp_dir(), '/\\') . '/pokerclock_state.json';
    // If fallback doesn't exist yet but primary file exists (read-only), copy initial content over
    if (!file_exists($fallbackFile) && file_exists($primaryFile)) {
        $initialContent = @file_get_contents($primaryFile);
        if ($initialContent) {
            @file_put_contents($fallbackFile, $initialContent);
        }
    }
    $resolvedPath = $fallbackFile;
    return $resolvedPath;
}

/**
 * Default seed data matching the reference Poker Clock screenshots + standard structure
 */
function get_default_state() {
    return [
        'settings' => [
            'brand_name' => 'POKER CLOCK',
            'brand_logo_icon' => '♠',
            'fire_effect' => true,
            'dual_left_id' => 'tourney_1',
            'dual_right_id' => 'tourney_2',
            'dual_muted' => true,
            'announcement_interval' => 8,
            'voice_enabled' => false,
        ],
        'tournaments' => [
            'tourney_1' => [
                'id' => 'tourney_1',
                'title' => '3400限時錦標賽_1',
                'subtitle' => '撲克王積分榜倒數1天',
                'theme' => 'gold',
                'status' => 'paused',
                'current_level_index' => 1,
                'remaining_seconds' => 3521, // 58:41
                'last_updated_at' => time(),
                'sound_enabled' => true,
                'right_top_mode' => 'auto',
                'current_players' => 18,
                'total_entries' => 20,
                'starting_chips' => 500000,
                'rebuy_count' => 0,
                'rebuy_chips' => 500000,
                'addon_count' => 0,
                'addon_chips' => 500000,
                'manual_total_chips' => 10000000,
                'buyin_prize' => 3000,
                'manual_prize_pool' => 60000,
                'announcements' => [
                    [
                        'title' => '賽事規則',
                        'content' => "Rebuy優於候補\n限時錦標賽時間到立即結算計分牌"
                    ],
                    [
                        'title' => '賽事規則',
                        'content' => "Rebuy優於候補"
                    ]
                ],
                'footer_notice' => '',
                'levels' => [
                    [
                        'type' => 'level',
                        'level' => 1,
                        'sb' => 1000,
                        'bb' => 3000,
                        'ante' => 0,
                        'duration' => 1800
                    ],
                    [
                        'type' => 'level',
                        'level' => 2,
                        'sb' => 2000,
                        'bb' => 4000,
                        'ante' => 0,
                        'duration' => 3600
                    ]
                ]
            ],
            'tourney_2' => [
                'id' => 'tourney_2',
                'title' => 'iPhone 18 pro搶先賽',
                'subtitle' => 'DAILY TOURNAMENT • 每日錦標賽',
                'theme' => 'cyan',
                'status' => 'paused',
                'current_level_index' => 16,
                'remaining_seconds' => 521, // 08:41
                'last_updated_at' => time(),
                'sound_enabled' => true,
                'right_top_mode' => 'break',
                'current_players' => 10,
                'total_entries' => 61,
                'starting_chips' => 30000,
                'rebuy_count' => 0,
                'rebuy_chips' => 30000,
                'addon_count' => 0,
                'addon_chips' => 30000,
                'manual_total_chips' => 1830000,
                'buyin_prize' => 650,
                'manual_prize_pool' => 39650,
                'announcements' => [
                    [
                        'title' => '獎池分配',
                        'content' => "1th iPhone 18 pro\n2nd 8,000\n3rd 5,000"
                    ]
                ],
                'footer_notice' => '折扣額由領域補貼獎池',
                'levels' => get_standard_18_levels()
            ]
        ]
    ];
}

function get_standard_18_levels() {
    $structure = [
        ['sb' => 100, 'bb' => 100, 'ante' => 100, 'duration' => 900],
        ['sb' => 100, 'bb' => 200, 'ante' => 200, 'duration' => 900],
        ['sb' => 100, 'bb' => 300, 'ante' => 300, 'duration' => 900],
        ['sb' => 200, 'bb' => 400, 'ante' => 400, 'duration' => 900],
        ['sb' => 300, 'bb' => 600, 'ante' => 600, 'duration' => 900],
        ['sb' => 400, 'bb' => 800, 'ante' => 800, 'duration' => 900],
        ['sb' => 500, 'bb' => 1000, 'ante' => 1000, 'duration' => 900],
        ['sb' => 600, 'bb' => 1200, 'ante' => 1200, 'duration' => 900],
        ['sb' => 800, 'bb' => 1600, 'ante' => 1600, 'duration' => 900],
        ['sb' => 1000, 'bb' => 2000, 'ante' => 2000, 'duration' => 900],
        ['sb' => 1000, 'bb' => 2500, 'ante' => 2500, 'duration' => 900],
        ['sb' => 1500, 'bb' => 3000, 'ante' => 3000, 'duration' => 900],
        ['sb' => 2000, 'bb' => 4000, 'ante' => 4000, 'duration' => 900],
        ['sb' => 2500, 'bb' => 5000, 'ante' => 5000, 'duration' => 900],
        ['sb' => 3000, 'bb' => 6000, 'ante' => 6000, 'duration' => 900],
        ['sb' => 3500, 'bb' => 7000, 'ante' => 7000, 'duration' => 900],
        ['sb' => 4000, 'bb' => 8000, 'ante' => 8000, 'duration' => 900],
        ['sb' => 5000, 'bb' => 10000, 'ante' => 10000, 'duration' => 900],
    ];
    $levels = [];
    foreach ($structure as $idx => $item) {
        $levels[] = [
            'type' => 'level',
            'level' => $idx + 1,
            'sb' => $item['sb'],
            'bb' => $item['bb'],
            'ante' => $item['ante'],
            'duration' => $item['duration']
        ];
    }
    $levels[] = [
        'type' => 'break',
        'level' => '休息',
        'sb' => 0,
        'bb' => 0,
        'ante' => 0,
        'duration' => 600
    ];
    $more = [
        ['sb' => 6000, 'bb' => 12000, 'ante' => 12000, 'duration' => 1800],
        ['sb' => 8000, 'bb' => 16000, 'ante' => 16000, 'duration' => 1800],
        ['sb' => 10000, 'bb' => 20000, 'ante' => 20000, 'duration' => 1800],
        ['sb' => 15000, 'bb' => 30000, 'ante' => 30000, 'duration' => 1800],
        ['sb' => 20000, 'bb' => 40000, 'ante' => 40000, 'duration' => 1800],
        ['sb' => 25000, 'bb' => 50000, 'ante' => 50000, 'duration' => 1800],
        ['sb' => 30000, 'bb' => 60000, 'ante' => 60000, 'duration' => 1800],
        ['sb' => 40000, 'bb' => 80000, 'ante' => 80000, 'duration' => 1800],
        ['sb' => 50000, 'bb' => 100000, 'ante' => 100000, 'duration' => 2100],
        ['sb' => 75000, 'bb' => 150000, 'ante' => 150000, 'duration' => 2100],
    ];
    $lvlNum = 19;
    foreach ($more as $item) {
        $levels[] = [
            'type' => 'level',
            'level' => $lvlNum++,
            'sb' => $item['sb'],
            'bb' => $item['bb'],
            'ante' => $item['ante'],
            'duration' => $item['duration']
        ];
    }
    return $levels;
}

/**
 * Load state and advance running timers based on elapsed wall-clock time
 */
function load_state() {
    $filePath = get_state_file_path();

    if (!file_exists($filePath)) {
        $defaultState = get_default_state();
        save_state($defaultState);
    }

    $state = null;
    if (file_exists($filePath)) {
        $fp = @fopen($filePath, 'r');
        if ($fp) {
            @flock($fp, LOCK_SH);
            $json = stream_get_contents($fp);
            @flock($fp, LOCK_UN);
            fclose($fp);
            $state = json_decode($json, true);
        }
    }

    if (!is_array($state) || !isset($state['tournaments'])) {
        $state = get_default_state();
    }

    $dirty = false;
    $now = time();

    if (($state['settings']['brand_name'] ?? '') === 'POKER DOMAIN') {
        $state['settings']['brand_name'] = 'POKER CLOCK';
        $dirty = true;
    }

    foreach ($state['tournaments'] as $id => &$t) {
        if (($t['status'] ?? 'paused') === 'running') {
            $elapsed = $now - ($t['last_updated_at'] ?? $now);
            if ($elapsed > 0) {
                $dirty = true;
                $t['last_updated_at'] = $now;
                $rem = ($t['remaining_seconds'] ?? 0) - $elapsed;

                while ($rem <= 0 && $t['status'] === 'running') {
                    $nextIndex = $t['current_level_index'] + 1;
                    if (isset($t['levels'][$nextIndex])) {
                        $t['current_level_index'] = $nextIndex;
                        $nextDuration = max(1, intval($t['levels'][$nextIndex]['duration'] ?? 900));
                        $rem += $nextDuration;
                    } else {
                        $rem = 0;
                        $t['status'] = 'finished';
                        break;
                    }
                }
                $t['remaining_seconds'] = max(0, $rem);
            }
        } else {
            $t['last_updated_at'] = $now;
        }
    }
    unset($t);

    if ($dirty) {
        save_state($state);
    }

    return enrich_state($state);
}

/**
 * Save state atomically
 */
function save_state($state) {
    $filePath = get_state_file_path();

    if (isset($state['tournaments'])) {
        foreach ($state['tournaments'] as &$t) {
            unset($t['computed']);
        }
        unset($t);
    }
    unset($state['server_time']);

    $fp = @fopen($filePath, 'c+');
    if ($fp) {
        @flock($fp, LOCK_EX);
        @ftruncate($fp, 0);
        @rewind($fp);
        @fwrite($fp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @fflush($fp);
        @flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * Compute derived fields for each tournament
 */
function enrich_state($state) {
    $state['server_time'] = time();

    foreach ($state['tournaments'] as $id => &$t) {
        $levels = $t['levels'] ?? [];
        $idx = intval($t['current_level_index'] ?? 0);
        if ($idx < 0) $idx = 0;
        if ($idx >= count($levels) && count($levels) > 0) {
            $idx = count($levels) - 1;
        }
        $t['current_level_index'] = $idx;

        $currentLevel = $levels[$idx] ?? [
            'type' => 'level',
            'level' => 1,
            'sb' => 100,
            'bb' => 200,
            'ante' => 0,
            'duration' => 900
        ];

        $nextLevel = $levels[$idx + 1] ?? null;
        $rem = max(0, intval($t['remaining_seconds'] ?? 0));
        $duration = max(1, intval($currentLevel['duration'] ?? 900));

        $timeToBreak = null;
        $timeToEnd = $rem;

        for ($i = $idx + 1; $i < count($levels); $i++) {
            $lvlDur = max(0, intval($levels[$i]['duration'] ?? 0));
            $timeToEnd += $lvlDur;
        }

        if (($currentLevel['type'] ?? 'level') === 'break') {
            $timeToBreak = 0;
        } else {
            $accBreak = $rem;
            $foundBreak = false;
            for ($i = $idx + 1; $i < count($levels); $i++) {
                if (($levels[$i]['type'] ?? 'level') === 'break') {
                    $foundBreak = true;
                    break;
                }
                $accBreak += max(0, intval($levels[$i]['duration'] ?? 0));
            }
            if ($foundBreak) {
                $timeToBreak = $accBreak;
            }
        }

        $entries = max(0, intval($t['total_entries'] ?? 0));
        $currentPlayers = max(0, intval($t['current_players'] ?? 0));
        $startingChips = max(0, intval($t['starting_chips'] ?? 0));
        $rebuyCount = max(0, intval($t['rebuy_count'] ?? 0));
        $rebuyChips = max(0, intval($t['rebuy_chips'] ?? 0));
        $addonCount = max(0, intval($t['addon_count'] ?? 0));
        $addonChips = max(0, intval($t['addon_chips'] ?? 0));

        $totalChips = intval($t['manual_total_chips'] ?? 0);
        if ($totalChips <= 0) {
            $totalChips = ($entries * $startingChips) + ($rebuyCount * $rebuyChips) + ($addonCount * $addonChips);
        }

        $avgChips = ($currentPlayers > 0) ? round($totalChips / $currentPlayers) : 0;
        $currentBB = intval($currentLevel['bb'] ?? 0);
        $avgBB = ($currentBB > 0 && $avgChips > 0) ? round($avgChips / $currentBB, 1) : 0;

        $prizePool = intval($t['manual_prize_pool'] ?? 0);
        if ($prizePool <= 0) {
            $buyinPrize = max(0, intval($t['buyin_prize'] ?? 0));
            $prizePool = ($entries + $rebuyCount + $addonCount) * $buyinPrize;
        }

        $progressPercent = min(100, max(0, round(($rem / $duration) * 100, 2)));

        $t['computed'] = [
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'time_to_break' => $timeToBreak,
            'time_to_end' => $timeToEnd,
            'total_chips' => $totalChips,
            'avg_chips' => $avgChips,
            'avg_bb' => $avgBB,
            'prize_pool' => $prizePool,
            'progress_percent' => $progressPercent
        ];
    }
    unset($t);

    return $state;
}
