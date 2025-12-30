<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Season.php';
require_once __DIR__ . '/../models/SwimEvent.php';

class StandingsController {
    public static function index() {
        require_login();
        require_role(['admin','organizer','coach']);
        $seasons = Season::all();
        $events = SwimEvent::all();
        $season_id = (int)($_GET['season_id'] ?? ($seasons[0]['id'] ?? 0));
        $swim_event_id = (int)($_GET['swim_event_id'] ?? 0); // optional
        $meets = DB::fetchAll('SELECT m.id, m.name, m.meet_on FROM meets m JOIN events e ON m.event_id=e.id WHERE e.season_id=? ORDER BY m.meet_on DESC', [$season_id]);
        $meet_id = (int)($_GET['meet_id'] ?? 0); // optional

        $params = [$season_id];
        $sql = 'SELECT c.id AS club_id, COALESCE(c.name, "(No Club)") AS club_name,
                       SUM(CASE pos WHEN 1 THEN 25 WHEN 2 THEN 18 WHEN 3 THEN 15 WHEN 4 THEN 10 WHEN 5 THEN 10 WHEN 6 THEN 10 WHEN 7 THEN 10 WHEN 8 THEN 10 ELSE 0 END) AS total_medal_points,
                       SUM(CASE WHEN pos=1 THEN 1 ELSE 0 END) AS gold,
                       SUM(CASE WHEN pos=2 THEN 1 ELSE 0 END) AS silver,
                       SUM(CASE WHEN pos=3 THEN 1 ELSE 0 END) AS bronze,
                       SUM(CASE WHEN pos BETWEEN 4 AND 8 THEN 1 ELSE 0 END) AS finalist
                FROM (
                  SELECT r.*, 1 + (
                    SELECT COUNT(*) FROM results r2
                    WHERE r2.race_id = r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms
                  ) AS pos
                  FROM results r
                ) r
                JOIN athletes a ON r.athlete_id=a.id
                JOIN races rc ON r.race_id=rc.id
                JOIN meets m ON rc.meet_id=m.id
                JOIN events e ON m.event_id=e.id
                JOIN seasons s ON e.season_id=s.id
                LEFT JOIN clubs c ON a.club_id=c.id
                WHERE s.id = ? AND r.status = "OK"';
        if ($swim_event_id) { $sql .= ' AND rc.swim_event_id = ?'; $params[] = $swim_event_id; }
        if ($meet_id) { $sql .= ' AND m.id = ?'; $params[] = $meet_id; }
        $sql .= ' GROUP BY c.id, c.name';
        $rows = DB::fetchAll($sql, $params);
        usort($rows, function($a,$b){
            if ((int)$b['total_medal_points'] !== (int)$a['total_medal_points']) return (int)$b['total_medal_points'] <=> (int)$a['total_medal_points'];
            if ((int)$b['gold'] !== (int)$a['gold']) return (int)$b['gold'] <=> (int)$a['gold'];
            if ((int)$b['silver'] !== (int)$a['silver']) return (int)$b['silver'] <=> (int)$a['silver'];
            return (int)$b['bronze'] <=> (int)$a['bronze'];
        });

        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/standings/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function exportCsv() {
        require_login();
        require_role(['admin','organizer','coach']);
        $season_id = (int)($_GET['season_id'] ?? 0);
        $swim_event_id = (int)($_GET['swim_event_id'] ?? 0);
        $meet_id = (int)($_GET['meet_id'] ?? 0);
        if (!$season_id) { http_response_code(400); echo 'season_id required'; return; }
        $params = [$season_id];
        $sql = 'SELECT COALESCE(c.name, "(No Club)") AS club_name,
                       SUM(CASE pos WHEN 1 THEN 25 WHEN 2 THEN 18 WHEN 3 THEN 15 WHEN 4 THEN 10 WHEN 5 THEN 10 WHEN 6 THEN 10 WHEN 7 THEN 10 WHEN 8 THEN 10 ELSE 0 END) AS total_medal_points,
                       SUM(CASE WHEN pos=1 THEN 1 ELSE 0 END) AS gold,
                       SUM(CASE WHEN pos=2 THEN 1 ELSE 0 END) AS silver,
                       SUM(CASE WHEN pos=3 THEN 1 ELSE 0 END) AS bronze,
                       SUM(CASE WHEN pos BETWEEN 4 AND 8 THEN 1 ELSE 0 END) AS finalist
                FROM (
                  SELECT r.*, 1 + (
                    SELECT COUNT(*) FROM results r2
                    WHERE r2.race_id = r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms
                  ) AS pos
                  FROM results r
                ) r
                JOIN athletes a ON r.athlete_id=a.id
                JOIN races rc ON r.race_id=rc.id
                JOIN meets m ON rc.meet_id=m.id
                JOIN events e ON m.event_id=e.id
                JOIN seasons s ON e.season_id=s.id
                LEFT JOIN clubs c ON a.club_id=c.id
                WHERE s.id = ? AND r.status = "OK"';
        if ($swim_event_id) { $sql .= ' AND rc.swim_event_id = ?'; $params[] = $swim_event_id; }
        if ($meet_id) { $sql .= ' AND m.id = ?'; $params[] = $meet_id; }
        $sql .= ' GROUP BY c.id, c.name';
        $rows = DB::fetchAll($sql, $params);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="standings.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Club','Gold','Silver','Bronze','Finalist','Medal Points']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['club_name'], $r['gold'], $r['silver'], $r['bronze'], $r['finalist'], $r['total_medal_points']]);
        }
        fclose($out);
    }
}
