<?php
require_once __DIR__ . '/../lib/db.php';

class SwolfTarget {
    private static function ensureTable() {
        // Create table if not exists (for existing installs)
        DB::exec('CREATE TABLE IF NOT EXISTS athlete_swolf_targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            athlete_id INT NOT NULL,
            stroke_type VARCHAR(20) NOT NULL,
            target_swolf INT NOT NULL,
            UNIQUE KEY uq_ast (athlete_id, stroke_type),
            FOREIGN KEY (athlete_id) REFERENCES athletes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
    }

    public static function listByAthlete(int $athlete_id): array {
        self::ensureTable();
        return DB::fetchAll('SELECT stroke_type, target_swolf FROM athlete_swolf_targets WHERE athlete_id=?', [$athlete_id]);
    }

    public static function upsert(int $athlete_id, string $stroke_type, int $target): void {
        self::ensureTable();
        $row = DB::fetch('SELECT id FROM athlete_swolf_targets WHERE athlete_id=? AND stroke_type=?', [$athlete_id, $stroke_type]);
        if ($row) {
            DB::exec('UPDATE athlete_swolf_targets SET target_swolf=? WHERE id=?', [$target, $row['id']]);
        } else {
            DB::exec('INSERT INTO athlete_swolf_targets(athlete_id,stroke_type,target_swolf) VALUES (?,?,?)', [$athlete_id, $stroke_type, $target]);
        }
    }
}

