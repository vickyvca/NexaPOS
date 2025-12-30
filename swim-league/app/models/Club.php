<?php
require_once __DIR__ . '/../lib/db.php';

class Club {
    public static function all(): array {
        return DB::fetchAll('SELECT * FROM clubs ORDER BY name ASC');
    }
}

