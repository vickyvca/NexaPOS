<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Leaderboard.php';

class ApiController {
    public static function activities() {
        require_login();
        header('Content-Type: application/json');
        $athlete_id = $_GET['athlete_id'] ?? 'me';
        $limit = (int)($_GET['limit'] ?? 20);
        if ($athlete_id === 'me') {
            $user = current_user();
            $ath = Athlete::findByUserId($user['id']);
            $athlete_id = $ath ? $ath['id'] : 0;
        }
        $data = $athlete_id ? Activity::latestByAthlete($athlete_id, $limit) : [];
        echo json_encode(['data' => $data]);
    }

    public static function leaderboards() {
        require_login();
        header('Content-Type: application/json');
        $season_id = (int)($_GET['season_id'] ?? 1);
        $swim_event_id = (int)($_GET['swim_event_id'] ?? 1);
        $age_group = $_GET['age_group'] ?? null;
        $gender = $_GET['gender'] ?? null;
        $rows = Leaderboard::byFilters($season_id, $swim_event_id, $gender ?: null, $age_group ?: null);
        echo json_encode(['data' => $rows]);
    }
}

