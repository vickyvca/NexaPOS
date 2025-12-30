<?php
require_once __DIR__ . '/../lib/db.php';

class Meet {
    public static function all(): array {
        return DB::fetchAll('SELECT m.*, e.name AS event_name, s.name AS season_name FROM meets m JOIN events e ON m.event_id=e.id JOIN seasons s ON e.season_id=s.id ORDER BY m.meet_on DESC');
    }
    public static function create(array $data): string {
        DB::exec('INSERT INTO meets(event_id,name,meet_on,venue) VALUES (?,?,?,?)', [
            $data['event_id'], $data['name'], $data['meet_on'], $data['venue'] ?? null
        ]);
        return DB::lastId();
    }
}

