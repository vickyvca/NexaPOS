<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/csv.php';
require_once __DIR__ . '/../models/Meet.php';
require_once __DIR__ . '/../models/Result.php';
require_once __DIR__ . '/../lib/competition.php';

class MeetController {
    public static function index() {
        require_login();
        $meets = Meet::all();
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/meets/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function create() {
        require_login();
        $error = null; $ok = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else {
                $data = [
                    'event_id' => (int)($_POST['event_id'] ?? 0),
                    'name' => trim($_POST['name'] ?? ''),
                    'meet_on' => $_POST['meet_on'] ?? date('Y-m-d'),
                    'venue' => trim($_POST['venue'] ?? '')
                ];
                if (!$data['event_id'] || !$data['name']) $error = 'Event and Name required';
                else { Meet::create($data); $ok = 'Meet created'; }
            }
        }
        $events = DB::fetchAll('SELECT e.id, e.name, s.name AS season FROM events e JOIN seasons s ON e.season_id=s.id ORDER BY s.start_date DESC');
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/meets/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function entries() {
        require_login();
        $error = null; $ok = null;
        $race_id = (int)($_GET['race_id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else if (($_POST['action'] ?? '') === 'auto_seed') {
                $method = $_POST['seed_method'] ?? 'center';
                if ($method === 'center') reseed_lanes_center_out($race_id, (int)($_POST['lane_count'] ?? 8));
                else reseed_lanes_simple($race_id);
                $ok = 'Lanes reseeded (' . h($method) . ')';
            } else if (($_POST['action'] ?? '') === 'auto_split') {
                $lane_count = (int)($_POST['lane_count'] ?? 8);
                if ($lane_count <= 0) $lane_count = 8;
                split_heats_center_out($race_id, $lane_count);
                $ok = 'Heats auto-split and lanes seeded';
            } else {
                $athlete_id = (int)($_POST['athlete_id'] ?? 0);
                $lane = (int)($_POST['lane'] ?? 0);
                // Validate gender and age group eligibility
                $raceDetail = DB::fetch('SELECT rc.*, se.gender AS se_gender, s.start_date AS season_start
                    FROM races rc
                    JOIN swim_events se ON rc.swim_event_id=se.id
                    JOIN meets m ON rc.meet_id=m.id
                    JOIN events e ON m.event_id=e.id
                    JOIN seasons s ON e.season_id=s.id
                    WHERE rc.id=?', [$race_id]);
                $ath = DB::fetch('SELECT * FROM athletes WHERE id=?', [$athlete_id]);
                if ($raceDetail && $ath) {
                    if ($raceDetail['se_gender'] !== 'X' && $raceDetail['se_gender'] !== $ath['gender']) {
                        $error = 'Athlete gender not eligible for this race';
                    } else {
                        $ag = null;
                        if (preg_match('/U(12|14|16|18)|Open/i', (string)$raceDetail['round_name'], $mch)) {
                            $ag = strtoupper($mch[0]);
                        }
                        if ($ag) {
                            $calc = age_group($ath['birthdate'], $raceDetail['season_start']);
                            if ($calc !== $ag) {
                                $error = 'Athlete age group (' . $calc . ') not eligible (' . $ag . ')';
                            }
                        }
                    }
                }
                if (!$error) {
                    $res = RaceEntry::add($race_id, $athlete_id, $lane);
                    if ($res['ok']) $ok = 'Entry added'; else $error = $res['error'];
                }
            }
        }
        $race = DB::fetch('SELECT rc.*, se.name AS swim_event FROM races rc JOIN swim_events se ON rc.swim_event_id=se.id WHERE rc.id=?', [$race_id]);
        $entries = DB::fetchAll('SELECT re.*, u.name AS athlete_name FROM race_entries re JOIN athletes a ON re.athlete_id=a.id JOIN users u ON a.user_id=u.id WHERE re.race_id=? ORDER BY lane', [$race_id]);
        $athletes = DB::fetchAll('SELECT a.id, u.name FROM athletes a JOIN users u ON a.user_id=u.id ORDER BY u.name');
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/meets/entries.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function importResultsCsv() {
        require_login();
        $error = null; $ok = null;
        $race_id = (int)($_GET['race_id'] ?? 0);
        $race = DB::fetch('SELECT * FROM races WHERE id=?', [$race_id]);
        if (!$race) { http_response_code(404); echo 'Race not found'; return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else if (!isset($_FILES['csv']) || !is_valid_csv_upload($_FILES['csv'])) {
                $error = 'Invalid CSV file';
            } else {
                $destDir = dirname(__DIR__, 2) . '/storage/uploads/results';
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                $fname = 'results_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['csv']['name']);
                $dest = $destDir . '/' . $fname;
                if (!move_uploaded_file($_FILES['csv']['tmp_name'], $dest)) {
                    $error = 'Failed to move upload';
                } else {
                    $rows = parse_csv_with_header($dest);
                    $required = ['race_id','lane','time_ms','status'];
                    $count = 0;
                    foreach ($rows as $r) {
                        foreach ($required as $col) { if (!array_key_exists($col, $r)) { $error = 'Missing column ' . $col; break 2; } }
                        if ((int)$r['race_id'] != $race_id) continue;
                        ResultModel::upsertByRaceLane($race_id, (int)$r['lane'], (int)$r['time_ms'], $r['status'], (int)$race['swim_event_id']);
                        $count++;
                    }
                    if (!$error) {
                        assign_medals_for_race($race_id);
                        $ok = "Imported/updated $count results + medals assigned";
                    }
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/meets/import_results.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
