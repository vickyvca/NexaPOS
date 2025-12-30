<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/csv.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Activity.php';

class ActivityController {
    public static function index() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $activities = $ath ? Activity::latestByAthlete($ath['id'], 50) : [];
        $summary = $ath ? Activity::monthSummary($ath['id']) : ['dist'=>0,'best_pace100'=>null];
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/activities/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function create() {
        require_login();
        $error = null; $ok = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else {
                $user = current_user();
                $ath = Athlete::findByUserId($user['id']);
                if (!$ath) { $error = 'No athlete profile'; }
                else {
                    $data = [
                        'athlete_id' => $ath['id'],
                        'activity_on' => $_POST['activity_on'] ?? date('Y-m-d'),
                        'distance_m' => (int)($_POST['distance_m'] ?? 0),
                        'duration_ms' => (int)($_POST['duration_ms'] ?? 0),
                        'pool_length_m' => (int)($_POST['pool_length_m'] ?? 0) ?: DEFAULT_POOL_LENGTH_M,
                        'stroke_type' => $_POST['stroke_type'] ?? null,
                        'avg_hr' => (int)($_POST['avg_hr'] ?? 0) ?: null,
                        'max_hr' => (int)($_POST['max_hr'] ?? 0) ?: null,
                        'calories' => (int)($_POST['calories'] ?? 0) ?: null,
                        'swolf' => (int)($_POST['swolf'] ?? 0) ?: null,
                    ];
                    if ($data['distance_m'] <= 0 || $data['duration_ms'] <= 0) {
                        $error = 'Distance and duration required';
                    } else {
                        Activity::create($data);
                        $ok = 'Activity added';
                    }
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/activities/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function importCsv() {
        require_login();
        $error = null; $ok = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else if (!isset($_FILES['csv']) || !is_valid_csv_upload($_FILES['csv'])) {
                $error = 'Invalid CSV file';
            } else {
                $destDir = dirname(__DIR__, 2) . '/storage/uploads/activities';
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                $fname = 'activities_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['csv']['name']);
                $dest = $destDir . '/' . $fname;
                if (!move_uploaded_file($_FILES['csv']['tmp_name'], $dest)) {
                    $error = 'Failed to move upload';
                } else {
                    $rows = parse_csv_with_header($dest);
                    $required = ['activity_on','distance_m','duration_ms','pool_length_m','stroke_type','avg_hr','max_hr','calories'];
                    $user = current_user();
                    $ath = Athlete::findByUserId($user['id']);
                    $count = 0;
                    foreach ($rows as $r) {
                        foreach ($required as $col) { if (!array_key_exists($col, $r)) { $error = 'Missing column ' . $col; break 2; } }
                        Activity::create([
                            'athlete_id' => $ath['id'],
                            'activity_on' => $r['activity_on'],
                            'distance_m' => (int)$r['distance_m'],
                            'duration_ms' => (int)$r['duration_ms'],
                            'pool_length_m' => (int)($r['pool_length_m'] ?? 0) ?: DEFAULT_POOL_LENGTH_M,
                            'stroke_type' => $r['stroke_type'] ?: null,
                            'avg_hr' => (int)$r['avg_hr'] ?: null,
                            'max_hr' => (int)$r['max_hr'] ?: null,
                            'calories' => (int)$r['calories'] ?: null,
                            'swolf' => null,
                        ]);
                        $count++;
                    }
                    if (!$error) $ok = "Imported $count activities";
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/activities/import.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
