<?php
require_once __DIR__ . '/../lib/db.php';

class Activity {
    public static function latestByAthlete($athlete_id, $limit = 20): array {
        return DB::fetchAll('SELECT * FROM activities WHERE athlete_id = ? ORDER BY activity_on DESC, id DESC LIMIT ?', [$athlete_id, (int)$limit]);
    }
    public static function create(array $data): string {
        DB::exec('INSERT INTO activities(athlete_id,activity_on,distance_m,duration_ms,pool_length_m,stroke_type,avg_hr,max_hr,calories,swolf) VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $data['athlete_id'], $data['activity_on'], $data['distance_m'], $data['duration_ms'], $data['pool_length_m'] ?? null,
            $data['stroke_type'] ?? null, $data['avg_hr'] ?? null, $data['max_hr'] ?? null, $data['calories'] ?? null, $data['swolf'] ?? null
        ]);
        return DB::lastId();
    }
    public static function monthSummary($athlete_id): array {
        $start = (new DateTime('first day of this month 00:00:00'))->format('Y-m-d');
        $row = DB::fetch('SELECT COALESCE(SUM(distance_m),0) dist, MIN(duration_ms*100.0/distance_m) best_pace100 FROM activities WHERE athlete_id=? AND activity_on>=?', [$athlete_id, $start]);
        return $row ?: ['dist' => 0, 'best_pace100' => null];
    }

    public static function stats($athlete_id): array {
        $month_start = (new DateTime('first day of this month 00:00:00'))->format('Y-m-d');
        // Week start: Monday
        $week_start_dt = new DateTime('monday this week');
        $week_start = $week_start_dt->format('Y-m-d');
        $m = DB::fetch('SELECT COALESCE(SUM(distance_m),0) month_dist, COALESCE(SUM(duration_ms),0) month_duration, COUNT(*) month_count, MIN(duration_ms*100.0/distance_m) best_pace100, AVG(NULLIF(swolf,0)) avg_swolf, AVG(NULLIF(avg_hr,0)) avg_hr FROM activities WHERE athlete_id=? AND activity_on>=?', [$athlete_id, $month_start]);
        $w = DB::fetch('SELECT COALESCE(SUM(distance_m),0) week_dist FROM activities WHERE athlete_id=? AND activity_on>=?', [$athlete_id, $week_start]);
        return [
            'month_dist' => (int)($m['month_dist'] ?? 0),
            'month_duration' => (int)($m['month_duration'] ?? 0),
            'month_count' => (int)($m['month_count'] ?? 0),
            'best_pace100' => $m['best_pace100'] ?? null,
            'week_dist' => (int)($w['week_dist'] ?? 0),
            'avg_swolf' => isset($m['avg_swolf']) ? (float)$m['avg_swolf'] : null,
            'avg_hr' => isset($m['avg_hr']) ? (float)$m['avg_hr'] : null,
        ];
    }
}
