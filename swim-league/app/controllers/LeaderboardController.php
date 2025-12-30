<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../models/Leaderboard.php';

class LeaderboardController {
    public static function index() {
        require_login();
        $season_id = (int)($_GET['season_id'] ?? 1);
        $swim_event_id = (int)($_GET['swim_event_id'] ?? 1);
        $gender = $_GET['gender'] ?? null;
        $age_group = $_GET['age_group'] ?? null;
        $rows = Leaderboard::byFilters($season_id, $swim_event_id, $gender ?: null, $age_group ?: null);
        $seasons = DB::fetchAll('SELECT id,name FROM seasons ORDER BY start_date DESC');
        $events = DB::fetchAll('SELECT id,name FROM swim_events ORDER BY id ASC');
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/leaderboards/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

