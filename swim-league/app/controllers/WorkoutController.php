<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Workout.php';
require_once __DIR__ . '/../models/Activity.php';

class WorkoutController {
    public static function index() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $list = $ath ? Workout::listForAthlete((int)$ath['id'], 100) : [];
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/workouts/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function complete() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $id = (int)($_GET['id'] ?? 0);
        $w = Workout::find($id);
        if (!$ath || !$w || (int)$w['athlete_id'] !== (int)$ath['id']) { http_response_code(404); echo 'Program tidak ditemukan'; return; }
        $ok=null; $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) $error='Token CSRF tidak valid';
            else {
                $data = [
                    'athlete_id' => (int)$ath['id'],
                    'activity_on' => $_POST['activity_on'] ?? $w['planned_on'],
                    'distance_m' => (int)($_POST['distance_m'] ?? 0),
                    'duration_ms' => (int)($_POST['duration_ms'] ?? 0),
                    'pool_length_m' => (int)($_POST['pool_length_m'] ?? 0) ?: DEFAULT_POOL_LENGTH_M,
                    'stroke_type' => $_POST['stroke_type'] ?? $w['stroke_type'],
                    'avg_hr' => (int)($_POST['avg_hr'] ?? 0) ?: null,
                    'max_hr' => (int)($_POST['max_hr'] ?? 0) ?: null,
                    'calories' => (int)($_POST['calories'] ?? 0) ?: null,
                    'swolf' => (int)($_POST['swolf'] ?? 0) ?: null,
                ];
                if ($data['distance_m']>0 && $data['duration_ms']>0) {
                    Activity::create($data);
                    Workout::markCompleted($id);
                    $ok = 'Aktivitas tercatat & program ditandai selesai';
                } else $error='Jarak & durasi wajib diisi';
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/workouts/complete.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

