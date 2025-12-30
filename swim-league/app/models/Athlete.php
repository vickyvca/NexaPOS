<?php
require_once __DIR__ . '/../lib/db.php';

class Athlete {
    public static function findByUserId($user_id): ?array {
        return DB::fetch('SELECT * FROM athletes WHERE user_id = ?', [$user_id]);
    }

    public static function upsertForUser(int $user_id, array $data): array {
        $existing = self::findByUserId($user_id);
        $gender = in_array(($data['gender'] ?? 'M'), ['M','F']) ? $data['gender'] : 'M';
        $birthdate = $data['birthdate'] ?? date('Y-m-d');
        $club_id = isset($data['club_id']) && $data['club_id'] ? (int)$data['club_id'] : null;
        if ($existing) {
            DB::exec('UPDATE athletes SET club_id=?, gender=?, birthdate=? WHERE id=?', [
                $club_id, $gender, $birthdate, $existing['id']
            ]);
            return ['ok'=>true, 'id'=>$existing['id'], 'updated'=>true];
        } else {
            DB::exec('INSERT INTO athletes(user_id,club_id,gender,birthdate) VALUES (?,?,?,?)', [
                $user_id, $club_id, $gender, $birthdate
            ]);
            return ['ok'=>true, 'id'=>DB::lastId(), 'created'=>true];
        }
    }
}
