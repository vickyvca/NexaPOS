<?php
require_once __DIR__ . '/../lib/db.php';

class Race {
    public static function create(array $data): string {
        DB::exec('INSERT INTO races(meet_id,swim_event_id,round_name,heat_no) VALUES (?,?,?,?)', [
            $data['meet_id'], $data['swim_event_id'], $data['round_name'] ?? 'Final', $data['heat_no'] ?? 1
        ]);
        return DB::lastId();
    }
}

