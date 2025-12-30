<?php
require_once __DIR__ . '/../lib/db.php';

class SwimEvent {
    public static function all(): array {
        return DB::fetchAll('SELECT * FROM swim_events ORDER BY distance_m ASC, stroke ASC');
    }
    public static function find($id): ?array {
        return DB::fetch('SELECT * FROM swim_events WHERE id=?', [$id]);
    }
    public static function findByDistanceStrokeGender(int $distance_m, string $stroke, string $gender = 'X'): ?array {
        return DB::fetch('SELECT * FROM swim_events WHERE distance_m=? AND stroke=? AND gender=?', [$distance_m, $stroke, $gender]);
    }
    public static function ensure(int $distance_m, string $stroke, ?string $name = null, string $gender = 'X'): int {
        $gender = in_array($gender, ['M','F','X'], true) ? $gender : 'X';
        $row = self::findByDistanceStrokeGender($distance_m, $stroke, $gender);
        if ($row) return (int)$row['id'];
        $name = $name ?: ($distance_m . 'm ' . $stroke . ($gender!=='X' ? ' ' . $gender : ''));
        DB::exec('INSERT INTO swim_events(name,distance_m,stroke,gender) VALUES (?,?,?,?)', [$name, $distance_m, $stroke, $gender]);
        return (int)DB::lastId();
    }
}
