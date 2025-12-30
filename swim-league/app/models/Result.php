<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/util.php';

class ResultModel {
    public static function upsertByRaceLane($race_id, $lane, $time_ms, $status, $swim_event_id): string {
        $entry = DB::fetch('SELECT * FROM race_entries WHERE race_id=? AND lane=?', [$race_id, $lane]);
        if (!$entry) return '';
        $points = $status === 'OK' ? compute_points((int)$swim_event_id, (int)$time_ms) : 0;
        $exists = DB::fetch('SELECT id FROM results WHERE race_id=? AND lane=?', [$race_id, $lane]);
        if ($exists) {
            DB::exec('UPDATE results SET time_ms=?, status=?, points=? WHERE id=?', [$time_ms, $status, $points, $exists['id']]);
            return $exists['id'];
        } else {
            DB::exec('INSERT INTO results(race_id,athlete_id,lane,time_ms,status,points) VALUES (?,?,?,?,?,?)', [
                $race_id, $entry['athlete_id'], $lane, $time_ms, $status, $points
            ]);
            return DB::lastId();
        }
    }
}

