<?php
require_once __DIR__ . '/../lib/db.php';

class Achievement {
    public static function ensure(string $code, string $name): int {
        $row = DB::fetch('SELECT id FROM achievements WHERE code=?', [$code]);
        if ($row) return (int)$row['id'];
        DB::exec('INSERT INTO achievements(code,name) VALUES (?,?)', [$code, $name]);
        return (int)DB::lastId();
    }

    public static function grant(int $athlete_id, string $code, ?string $date = null): bool {
        $ach_id = self::ensure($code, $code);
        $row = DB::fetch('SELECT id FROM athlete_achievements WHERE athlete_id=? AND achievement_id=?', [$athlete_id, $ach_id]);
        if ($row) return false;
        $date = $date ?: date('Y-m-d');
        DB::exec('INSERT INTO athlete_achievements(athlete_id,achievement_id,granted_on) VALUES (?,?,?)', [$athlete_id, $ach_id, $date]);
        return true;
    }

    public static function listByAthlete(int $athlete_id): array {
        return DB::fetchAll('SELECT aa.granted_on, ac.code, ac.name FROM athlete_achievements aa JOIN achievements ac ON aa.achievement_id=ac.id WHERE aa.athlete_id=? ORDER BY aa.granted_on DESC', [$athlete_id]);
    }
}

