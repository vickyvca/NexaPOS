<?php
require_once __DIR__ . '/../lib/db.php';

class Season {
    public static function all(): array {
        return DB::fetchAll('SELECT * FROM seasons ORDER BY start_date DESC');
    }
    public static function create(array $data): string {
        DB::exec('INSERT INTO seasons(name,start_date,end_date) VALUES (?,?,?)', [
            $data['name'], $data['start_date'], $data['end_date']
        ]);
        return DB::lastId();
    }
}

