<?php
/**
 * Poker Clock REST API
 * Handles timer synchronization, remote control commands, and tournament configuration
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/includes/storage.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $state = load_state();
    echo json_encode([
        'ok' => true,
        'data' => $state
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle POST requests
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? $action;
$state = load_state();
$now = time();

switch ($action) {
    case 'toggle_play': {
        $tid = $input['tournament_id'] ?? '';
        if (isset($state['tournaments'][$tid])) {
            $t = &$state['tournaments'][$tid];
            if ($t['status'] === 'running') {
                $t['status'] = 'paused';
            } else {
                if ($t['remaining_seconds'] <= 0) {
                    $idx = $t['current_level_index'] ?? 0;
                    $t['remaining_seconds'] = intval($t['levels'][$idx]['duration'] ?? 900);
                }
                $t['status'] = 'running';
            }
            $t['last_updated_at'] = $now;
            save_state($state);
        }
        break;
    }

    case 'change_level': {
        $tid = $input['tournament_id'] ?? '';
        if (isset($state['tournaments'][$tid])) {
            $t = &$state['tournaments'][$tid];
            $levels = $t['levels'] ?? [];
            $count = count($levels);
            if ($count > 0) {
                if (isset($input['level_index'])) {
                    $newIdx = intval($input['level_index']);
                } else {
                    $delta = intval($input['delta'] ?? 0);
                    $newIdx = intval($t['current_level_index']) + $delta;
                }
                $newIdx = max(0, min($count - 1, $newIdx));
                $t['current_level_index'] = $newIdx;
                $t['remaining_seconds'] = max(1, intval($levels[$newIdx]['duration'] ?? 900));
                $t['last_updated_at'] = $now;
                if ($t['status'] === 'finished') {
                    $t['status'] = 'paused';
                }
                save_state($state);
            }
        }
        break;
    }

    case 'adjust_time': {
        $tid = $input['tournament_id'] ?? '';
        if (isset($state['tournaments'][$tid])) {
            $t = &$state['tournaments'][$tid];
            if (!empty($input['reset'])) {
                $idx = $t['current_level_index'] ?? 0;
                $t['remaining_seconds'] = max(1, intval($t['levels'][$idx]['duration'] ?? 900));
            } elseif (isset($input['set_seconds'])) {
                $t['remaining_seconds'] = max(0, intval($input['set_seconds']));
            } else {
                $delta = intval($input['delta'] ?? 0);
                $t['remaining_seconds'] = max(0, intval($t['remaining_seconds']) + $delta);
            }
            $t['last_updated_at'] = $now;
            save_state($state);
        }
        break;
    }

    case 'adjust_players': {
        $tid = $input['tournament_id'] ?? '';
        if (isset($state['tournaments'][$tid])) {
            $t = &$state['tournaments'][$tid];
            $subAction = $input['sub_action'] ?? '';
            if ($subAction === 'buyin') {
                // New player entry: +1 current, +1 total
                $t['current_players'] = max(0, intval($t['current_players']) + 1);
                $t['total_entries'] = max($t['current_players'], intval($t['total_entries']) + 1);
            } elseif ($subAction === 'minus_entry') {
                $t['total_entries'] = max(0, intval($t['total_entries']) - 1);
                if ($t['current_players'] > $t['total_entries']) {
                    $t['current_players'] = $t['total_entries'];
                }
            } elseif ($subAction === 'bust') {
                // Player eliminated: -1 current
                $t['current_players'] = max(0, intval($t['current_players']) - 1);
            } elseif ($subAction === 'revive') {
                // Undo bust: +1 current (up to total_entries)
                $t['current_players'] = min(intval($t['total_entries']), intval($t['current_players']) + 1);
            } elseif ($subAction === 'rebuy') {
                $delta = intval($input['delta'] ?? 1);
                $t['rebuy_count'] = max(0, intval($t['rebuy_count'] ?? 0) + $delta);
            } elseif ($subAction === 'addon') {
                $delta = intval($input['delta'] ?? 1);
                $t['addon_count'] = max(0, intval($t['addon_count'] ?? 0) + $delta);
            }
            save_state($state);
        }
        break;
    }

    case 'toggle_sound': {
        $tid = $input['tournament_id'] ?? '';
        if ($tid && isset($state['tournaments'][$tid])) {
            $state['tournaments'][$tid]['sound_enabled'] = !$state['tournaments'][$tid]['sound_enabled'];
            save_state($state);
        } elseif (!empty($input['dual'])) {
            $state['settings']['dual_muted'] = !$state['settings']['dual_muted'];
            save_state($state);
        }
        break;
    }

    case 'toggle_fire': {
        $state['settings']['fire_effect'] = !($state['settings']['fire_effect'] ?? true);
        save_state($state);
        break;
    }

    case 'save_tournament': {
        $tid = $input['tournament_id'] ?? '';
        $tData = $input['tournament'] ?? null;
        if ($tid && is_array($tData) && isset($state['tournaments'][$tid])) {
            $existing = $state['tournaments'][$tid];
            $existing['title'] = trim($tData['title'] ?? $existing['title']);
            $existing['subtitle'] = trim($tData['subtitle'] ?? $existing['subtitle']);
            $existing['theme'] = $tData['theme'] ?? $existing['theme'];
            $existing['right_top_mode'] = $tData['right_top_mode'] ?? ($existing['right_top_mode'] ?? 'auto');
            $existing['current_players'] = max(0, intval($tData['current_players'] ?? $existing['current_players']));
            $existing['total_entries'] = max(0, intval($tData['total_entries'] ?? $existing['total_entries']));
            $existing['starting_chips'] = max(0, intval($tData['starting_chips'] ?? $existing['starting_chips']));
            $existing['rebuy_count'] = max(0, intval($tData['rebuy_count'] ?? $existing['rebuy_count']));
            $existing['rebuy_chips'] = max(0, intval($tData['rebuy_chips'] ?? $existing['rebuy_chips']));
            $existing['addon_count'] = max(0, intval($tData['addon_count'] ?? $existing['addon_count']));
            $existing['addon_chips'] = max(0, intval($tData['addon_chips'] ?? $existing['addon_chips']));
            $existing['manual_total_chips'] = max(0, intval($tData['manual_total_chips'] ?? 0));
            $existing['buyin_prize'] = max(0, intval($tData['buyin_prize'] ?? $existing['buyin_prize']));
            $existing['manual_prize_pool'] = max(0, intval($tData['manual_prize_pool'] ?? 0));
            $existing['footer_notice'] = trim($tData['footer_notice'] ?? '');

            if (isset($tData['announcements']) && is_array($tData['announcements'])) {
                $cleanAnn = [];
                foreach ($tData['announcements'] as $ann) {
                    if (trim($ann['title'] ?? '') !== '' || trim($ann['content'] ?? '') !== '') {
                        $cleanAnn[] = [
                            'title' => trim($ann['title'] ?? '賽事公告'),
                            'content' => trim($ann['content'] ?? '')
                        ];
                    }
                }
                $existing['announcements'] = $cleanAnn;
            }

            if (isset($tData['levels']) && is_array($tData['levels']) && count($tData['levels']) > 0) {
                $cleanLevels = [];
                $lvlCounter = 1;
                foreach ($tData['levels'] as $lvl) {
                    $type = ($lvl['type'] ?? 'level') === 'break' ? 'break' : 'level';
                    $cleanLevels[] = [
                        'type' => $type,
                        'level' => $type === 'break' ? ($lvl['level'] ?: '休息') : $lvlCounter++,
                        'sb' => $type === 'break' ? 0 : max(0, intval($lvl['sb'] ?? 0)),
                        'bb' => $type === 'break' ? 0 : max(0, intval($lvl['bb'] ?? 0)),
                        'ante' => $type === 'break' ? 0 : max(0, intval($lvl['ante'] ?? 0)),
                        'duration' => max(10, intval($lvl['duration'] ?? 900))
                    ];
                }
                $existing['levels'] = $cleanLevels;
                if ($existing['current_level_index'] >= count($cleanLevels)) {
                    $existing['current_level_index'] = count($cleanLevels) - 1;
                    $existing['remaining_seconds'] = $cleanLevels[$existing['current_level_index']]['duration'];
                }
            }

            $state['tournaments'][$tid] = $existing;
            save_state($state);
        }
        break;
    }

    case 'create_tournament': {
        $newId = 'tourney_' . uniqid();
        $template = $input['template'] ?? 'standard';
        $title = trim($input['title'] ?? '新錦標賽');
        $theme = $input['theme'] ?? 'gold';

        $levels = [];
        if ($template === 'turbo') {
            $blinds = [
                [100, 200, 200], [200, 400, 400], [300, 600, 600], [400, 800, 800],
                [500, 1000, 1000], [600, 1200, 1200], ['break', 600],
                [800, 1600, 1600], [1000, 2000, 2000], [1500, 3000, 3000],
                [2000, 4000, 4000], [3000, 6000, 6000], [4000, 8000, 8000],
                [5000, 10000, 10000], [8000, 16000, 16000], [10000, 20000, 20000]
            ];
            $n = 1;
            foreach ($blinds as $b) {
                if ($b[0] === 'break') {
                    $levels[] = ['type' => 'break', 'level' => '休息', 'sb' => 0, 'bb' => 0, 'ante' => 0, 'duration' => $b[1]];
                } else {
                    $levels[] = ['type' => 'level', 'level' => $n++, 'sb' => $b[0], 'bb' => $b[1], 'ante' => $b[2], 'duration' => 600]; // 10 min
                }
            }
        } elseif ($template === 'timed') {
            $levels = [
                ['type' => 'level', 'level' => 1, 'sb' => 1000, 'bb' => 3000, 'ante' => 0, 'duration' => 1800],
                ['type' => 'level', 'level' => 2, 'sb' => 2000, 'bb' => 4000, 'ante' => 0, 'duration' => 3600],
            ];
        } else {
            // Standard 20-min levels
            $blinds = [
                [100, 200, 200], [200, 400, 400], [300, 600, 600], [400, 800, 800],
                ['break', 900],
                [500, 1000, 1000], [600, 1200, 1200], [800, 1600, 1600], [1000, 2000, 2000],
                ['break', 900],
                [1500, 3000, 3000], [2000, 4000, 4000], [2500, 5000, 5000], [3000, 6000, 6000],
                [4000, 8000, 8000], [5000, 10000, 10000]
            ];
            $n = 1;
            foreach ($blinds as $b) {
                if ($b[0] === 'break') {
                    $levels[] = ['type' => 'break', 'level' => '休息', 'sb' => 0, 'bb' => 0, 'ante' => 0, 'duration' => $b[1]];
                } else {
                    $levels[] = ['type' => 'level', 'level' => $n++, 'sb' => $b[0], 'bb' => $b[1], 'ante' => $b[2], 'duration' => 1200]; // 20 min
                }
            }
        }

        $state['tournaments'][$newId] = [
            'id' => $newId,
            'title' => $title,
            'subtitle' => 'DAILY TOURNAMENT • 錦標賽',
            'theme' => $theme,
            'status' => 'paused',
            'current_level_index' => 0,
            'remaining_seconds' => $levels[0]['duration'],
            'last_updated_at' => $now,
            'sound_enabled' => true,
            'right_top_mode' => 'auto',
            'current_players' => 9,
            'total_entries' => 9,
            'starting_chips' => 30000,
            'rebuy_count' => 0,
            'rebuy_chips' => 30000,
            'addon_count' => 0,
            'addon_chips' => 30000,
            'manual_total_chips' => 0,
            'buyin_prize' => 1000,
            'manual_prize_pool' => 0,
            'announcements' => [
                [
                    'title' => '賽事規則',
                    'content' => "歡迎參加本次賽事\n請遵守現場裁判指示"
                ]
            ],
            'footer_notice' => '',
            'levels' => $levels
        ];
        save_state($state);
        break;
    }

    case 'delete_tournament': {
        $tid = $input['tournament_id'] ?? '';
        if (isset($state['tournaments'][$tid]) && count($state['tournaments']) > 1) {
            unset($state['tournaments'][$tid]);
            $keys = array_keys($state['tournaments']);
            if ($state['settings']['dual_left_id'] === $tid) {
                $state['settings']['dual_left_id'] = $keys[0];
            }
            if ($state['settings']['dual_right_id'] === $tid) {
                $state['settings']['dual_right_id'] = $keys[min(1, count($keys) - 1)];
            }
            save_state($state);
        }
        break;
    }

    case 'save_settings': {
        $s = $input['settings'] ?? [];
        if (is_array($s)) {
            if (isset($s['brand_name'])) $state['settings']['brand_name'] = trim($s['brand_name']);
            if (isset($s['brand_logo_icon'])) $state['settings']['brand_logo_icon'] = trim($s['brand_logo_icon']);
            if (isset($s['fire_effect'])) $state['settings']['fire_effect'] = (bool)$s['fire_effect'];
            if (isset($s['dual_left_id'])) $state['settings']['dual_left_id'] = $s['dual_left_id'];
            if (isset($s['dual_right_id'])) $state['settings']['dual_right_id'] = $s['dual_right_id'];
            if (isset($s['dual_muted'])) $state['settings']['dual_muted'] = (bool)$s['dual_muted'];
            if (isset($s['announcement_interval'])) $state['settings']['announcement_interval'] = max(3, intval($s['announcement_interval']));
            if (isset($s['voice_enabled'])) $state['settings']['voice_enabled'] = (bool)$s['voice_enabled'];
            save_state($state);
        }
        break;
    }

    case 'reset_demo': {
        $state = get_default_state();
        save_state($state);
        break;
    }
}

$enriched = load_state();
echo json_encode([
    'ok' => true,
    'data' => $enriched
], JSON_UNESCAPED_UNICODE);
