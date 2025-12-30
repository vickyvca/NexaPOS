<?php
require_once __DIR__ . '/../lib/db.php';

class Leaderboard {
    public static function byFilters($season_id, $swim_event_id, $gender = null, $age_group = null): array {
        $sql = 'SELECT a.id AS athlete_id, u.name AS athlete_name, a.gender, MIN(r.time_ms) AS best_time_ms, SUM(r.points) AS total_points
                FROM results r
                JOIN race_entries re ON r.race_id = re.race_id AND r.lane = re.lane
                JOIN races rc ON r.race_id = rc.id
                JOIN meets m ON rc.meet_id = m.id
                JOIN events e ON m.event_id = e.id
                JOIN athletes a ON r.athlete_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE e.season_id = ? AND rc.swim_event_id = ? AND r.status = "OK"';
        $params = [$season_id, $swim_event_id];
        if ($gender) {
            $sql .= ' AND a.gender = ?';
            $params[] = $gender;
        }
        $sql .= ' GROUP BY a.id, u.name, a.gender';
        $rows = DB::fetchAll($sql, $params);
        if ($age_group) {
            // filter in PHP due birthdate/season_start requirement
            $season = DB::fetch('SELECT start_date FROM seasons WHERE id=?', [$season_id]);
            $start = $season ? $season['start_date'] : date('Y-m-d');
            $filtered = [];
            foreach ($rows as $r) {
                $ath = DB::fetch('SELECT birthdate FROM athletes WHERE id=?', [$r['athlete_id']]);
                if (!$ath) continue;
                $group = age_group($ath['birthdate'], $start);
                if ($group === $age_group) $filtered[] = $r;
            }
            $rows = $filtered;
        }
        usort($rows, function($a,$b){
            if ((int)$b['total_points'] !== (int)$a['total_points']) return (int)$b['total_points'] <=> (int)$a['total_points'];
            return (int)$a['best_time_ms'] <=> (int)$b['best_time_ms'];
        });
        return $rows;
    }
}

