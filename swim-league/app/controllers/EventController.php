<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Season.php';

class EventController {
    public static function index() {
        require_login();
        require_role(['admin','organizer']);
        $events = EventModel::all();
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/events/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
    public static function create() {
        require_login();
        require_role(['admin','organizer']);
        $ok = null; $error = null;
        $seasons = Season::all();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Invalid CSRF token';
            else {
                $season_id = (int)($_POST['season_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                if (!$season_id || !$name) $error = 'Season and name required';
                else { EventModel::create(['season_id'=>$season_id,'name'=>$name]); $ok='Event created'; }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/events/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

