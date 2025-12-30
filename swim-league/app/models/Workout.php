<?php
require_once __DIR__ . '/../lib/db.php';

class Workout {
    public static function createForAthlete(int $coach_user_id, int $athlete_id, array $data): string {
        DB::exec('INSERT INTO workouts(coach_user_id,athlete_id,club_id,planned_on,title,description,target_distance_m,stroke_type,target_swolf,status) VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $coach_user_id, $athlete_id, $data['club_id'] ?? null, $data['planned_on'], $data['title'], $data['description'] ?? null,
            $data['target_distance_m'] ?? null, $data['stroke_type'] ?? null, $data['target_swolf'] ?? null, 'planned'
        ]);
        return DB::lastId();
    }

    public static function listForClub(int $club_id, ?string $on = null): array {
        $on = $on ?: date('Y-m-d');
        return DB::fetchAll('SELECT w.*, u.name AS athlete_name FROM workouts w JOIN athletes a ON w.athlete_id=a.id JOIN users u ON a.user_id=u.id WHERE w.club_id=? AND w.planned_on=? ORDER BY u.name', [$club_id, $on]);
    }

    public static function listForAthlete(int $athlete_id, int $limit = 50): array {
        return DB::fetchAll('SELECT * FROM workouts WHERE athlete_id=? ORDER BY planned_on DESC, id DESC LIMIT ?', [$athlete_id, $limit]);
    }

    public static function find(int $id): ?array {
        return DB::fetch('SELECT * FROM workouts WHERE id=?', [$id]);
    }

    public static function markCompleted(int $id): void {
        DB::exec('UPDATE workouts SET status="completed" WHERE id=?', [$id]);
    }
}

