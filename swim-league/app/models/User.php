<?php
require_once __DIR__ . '/../lib/db.php';

class User {
    public static function findById($id): ?array {
        return DB::fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }
    public static function findByEmail($email): ?array {
        return DB::fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }
}

