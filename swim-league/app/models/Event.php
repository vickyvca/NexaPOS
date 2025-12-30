<?php
require_once __DIR__ . '/../lib/db.php';

class EventModel {
    public static function all(): array {
        return DB::fetchAll('SELECT e.*, s.name AS season_name FROM events e JOIN seasons s ON e.season_id=s.id ORDER BY s.start_date DESC, e.id DESC');
    }
    public static function create(array $data): string {
        DB::exec('INSERT INTO events(season_id,name) VALUES (?,?)', [
            $data['season_id'], $data['name']
        ]);
        return DB::lastId();
    }
}

