<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Club.php';
require_once __DIR__ . '/../models/Athlete.php';

class TeamController {
    public static function index() {
        require_login();
        require_role(['admin','organizer','coach']);
        $clubs = Club::all();
        $counts = DB::fetchAll('SELECT club_id, COUNT(*) c FROM athletes GROUP BY club_id');
        $map = [];
        foreach ($counts as $c) { $map[$c['club_id']] = $c['c']; }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/team/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function club() {
        require_login();
        require_role(['admin','organizer','coach']);
        $club_id = (int)($_GET['club_id'] ?? 0);
        $club = DB::fetch('SELECT * FROM clubs WHERE id=?', [$club_id]);
        if (!$club) { http_response_code(404); echo 'Club not found'; return; }
        $athletes = DB::fetchAll('SELECT a.*, u.name AS user_name, u.email FROM athletes a JOIN users u ON a.user_id=u.id WHERE a.club_id=? ORDER BY u.name', [$club_id]);
        // For each athlete, basic stats
        foreach ($athletes as &$a) {
            $act = DB::fetch('SELECT COALESCE(SUM(distance_m),0) dist, MAX(activity_on) last_on FROM activities WHERE athlete_id=?', [$a['id']]);
            $pts = DB::fetch('SELECT COALESCE(SUM(points),0) pts, COUNT(*) rcnt FROM results WHERE athlete_id=? AND status="OK"', [$a['id']]);
            $a['total_dist'] = (int)($act['dist'] ?? 0);
            $a['last_activity_on'] = $act['last_on'] ?? null;
            $a['total_points'] = (int)($pts['pts'] ?? 0);
            $a['races_count'] = (int)($pts['rcnt'] ?? 0);
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/team/club.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

