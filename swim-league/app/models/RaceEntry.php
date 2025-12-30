<?php
require_once __DIR__ . '/../lib/db.php';

class RaceEntry {
    public static function add($race_id, $athlete_id, $lane): array {
        $existsLane = DB::fetch('SELECT id FROM race_entries WHERE race_id=? AND lane=?', [$race_id, $lane]);
        if ($existsLane) return ['ok'=>false,'error'=>'Lane already used'];
        $existsAth = DB::fetch('SELECT id FROM race_entries WHERE race_id=? AND athlete_id=?', [$race_id, $athlete_id]);
        if ($existsAth) return ['ok'=>false,'error'=>'Athlete already entered'];
        DB::exec('INSERT INTO race_entries(race_id,athlete_id,lane) VALUES (?,?,?)', [$race_id,$athlete_id,$lane]);
        return ['ok'=>true, 'id'=>DB::lastId()];
    }
}

