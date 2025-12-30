<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Club.php';
require_once __DIR__ . '/../models/Workout.php';

class CoachProgramController {
    public static function index() {
        require_login();
        require_role(['coach','organizer','admin']);
        $clubs = Club::all();
        $club_id = (int)($_GET['club_id'] ?? ($clubs[0]['id'] ?? 0));
        $date = $_GET['on'] ?? date('Y-m-d');
        $workouts = $club_id ? Workout::listForClub($club_id, $date) : [];
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/coach_programs/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function create() {
        require_login();
        require_role(['coach','organizer','admin']);
        $clubs = Club::all();
        $templates = [
            [
                'key' => 'aerobic_base',
                'title' => 'Aerobic Endurance',
                'description' => "Warmup 600m\nMain 10x200m Z2\nCool 200m",
                'target_distance_m' => 3000,
                'stroke_type' => 'FREE',
                'target_swolf' => 40,
            ],
            [
                'key' => 'threshold',
                'title' => 'Threshold',
                'description' => "WU 400m\nMain 5x400m Z3\nCD 200m",
                'target_distance_m' => 3200,
                'stroke_type' => 'FREE',
                'target_swolf' => 38,
            ],
            [
                'key' => 'sprint',
                'title' => 'Sprint',
                'description' => "WU 600m\nMain 16x50m Sprint, 30s rest\nCD 200m",
                'target_distance_m' => 2000,
                'stroke_type' => 'FREE',
                'target_swolf' => 36,
            ],
        ];
        $ok = null; $error = null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Token CSRF tidak valid';
            else {
                $club_id = (int)($_POST['club_id'] ?? 0);
                $planned_on = $_POST['planned_on'] ?? date('Y-m-d');
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $target_distance_m = (int)($_POST['target_distance_m'] ?? 0) ?: null;
                $stroke_type = $_POST['stroke_type'] ?? null;
                $target_swolf = (int)($_POST['target_swolf'] ?? 0) ?: null;
                $repeat_days = max(1, (int)($_POST['repeat_days'] ?? 1));
                $assign = $_POST['assign'] ?? 'club';
                if (!$club_id || !$title) $error = 'Klub dan judul wajib diisi';
                else {
                    $data = compact('club_id','planned_on','title','description','target_distance_m','stroke_type','target_swolf');
                    // fungsi membuat untuk tanggal berulang
                    $makeForDate = function($dateStr) use ($assign,$club_id,$data) {
                        if ($assign === 'club') {
                            $athletes = DB::fetchAll('SELECT id FROM athletes WHERE club_id=?', [$club_id]);
                            foreach ($athletes as $a) { $d=$data; $d['planned_on']=$dateStr; Workout::createForAthlete((int)current_user()['id'], (int)$a['id'], $d); }
                            return 'Program dibuat untuk semua atlet klub';
                        } else {
                            $athlete_id = (int)($_POST['athlete_id'] ?? 0);
                            if (!$athlete_id) return 'Pilih atlet';
                            $d=$data; $d['planned_on']=$dateStr; Workout::createForAthlete((int)current_user()['id'], $athlete_id, $d); return 'Program dibuat untuk atlet terpilih';
                        }
                    };
                    for ($i=0; $i<$repeat_days; $i++) {
                        $d = (new DateTime($planned_on))->modify("+$i day")->format('Y-m-d');
                        $ok = $makeForDate($d);
                    }
                }
            }
        }
        $athletes = [];
        if (!empty($clubs)) {
            $cid = (int)($_POST['club_id'] ?? $clubs[0]['id']);
            $athletes = DB::fetchAll('SELECT a.id, u.name FROM athletes a JOIN users u ON a.user_id=u.id WHERE a.club_id=? ORDER BY u.name', [$cid]);
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/coach_programs/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
