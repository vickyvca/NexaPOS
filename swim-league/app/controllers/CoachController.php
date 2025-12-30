<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Club.php';

class CoachController {
    public static function dashboard() {
        require_login();
        require_role(['coach','organizer','admin']);
        $clubs = Club::all();
        $club_id = (int)($_GET['club_id'] ?? ($clubs[0]['id'] ?? 0));
        $club = $club_id ? DB::fetch('SELECT * FROM clubs WHERE id=?', [$club_id]) : null;
        $summary = ['athletes'=>0,'week_dist'=>0,'month_dist'=>0,'medal_points'=>0,'podiums'=>0,'avg_swolf'=>null,'avg_hr'=>null,'month_act'=>0];
        $athletes = [];
        if ($club) {
            $athletes = DB::fetchAll('SELECT a.id, u.name FROM athletes a JOIN users u ON a.user_id=u.id WHERE a.club_id=? ORDER BY u.name', [$club_id]);
            $summary['athletes'] = count($athletes);
            $week_start = (new DateTime('monday this week'))->format('Y-m-d');
            $month_start = (new DateTime('first day of this month'))->format('Y-m-d');
            $w = DB::fetch('SELECT COALESCE(SUM(distance_m),0) d FROM activities WHERE athlete_id IN (SELECT id FROM athletes WHERE club_id=?) AND activity_on>=?', [$club_id,$week_start]);
            $m = DB::fetch('SELECT COALESCE(SUM(distance_m),0) d, COUNT(*) c, AVG(NULLIF(swolf,0)) avg_swolf, AVG(NULLIF(avg_hr,0)) avg_hr FROM activities WHERE athlete_id IN (SELECT id FROM athletes WHERE club_id=?) AND activity_on>=?', [$club_id,$month_start]);
            $mp = DB::fetch('SELECT COALESCE(SUM(
                    CASE (1 + (SELECT COUNT(*) FROM results r2 WHERE r2.race_id=r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms))
                      WHEN 1 THEN 25 WHEN 2 THEN 18 WHEN 3 THEN 15 WHEN 4 THEN 10 WHEN 5 THEN 10 WHEN 6 THEN 10 WHEN 7 THEN 10 WHEN 8 THEN 10 ELSE 0 END
                  ),0) p FROM results r WHERE r.athlete_id IN (SELECT id FROM athletes WHERE club_id=?) AND r.status="OK"', [$club_id]);
            $pod = DB::fetch('SELECT COUNT(*) c FROM results r WHERE r.athlete_id IN (SELECT id FROM athletes WHERE club_id=?) AND r.status="OK" AND (1 + (SELECT COUNT(*) FROM results r2 WHERE r2.race_id=r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms)) <= 3', [$club_id]);
            $summary['week_dist'] = (int)($w['d'] ?? 0);
            $summary['month_dist'] = (int)($m['d'] ?? 0);
            $summary['medal_points'] = (int)($mp['p'] ?? 0);
            $summary['podiums'] = (int)($pod['c'] ?? 0);
            $summary['avg_swolf'] = isset($m['avg_swolf']) ? (float)$m['avg_swolf'] : null;
            $summary['avg_hr'] = isset($m['avg_hr']) ? (float)$m['avg_hr'] : null;
            $summary['month_act'] = (int)($m['c'] ?? 0);
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/coach/dashboard.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
