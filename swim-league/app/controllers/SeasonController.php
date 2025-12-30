<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Season.php';

class SeasonController {
    public static function index() {
        require_login();
        require_role(['admin','organizer']);
        $seasons = Season::all();
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/seasons/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
    public static function create() {
        require_login();
        require_role(['admin','organizer']);
        $ok = null; $error = null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) $error = 'Invalid CSRF token';
            else {
                $name = trim($_POST['name'] ?? '');
                $start = $_POST['start_date'] ?? '';
                $end = $_POST['end_date'] ?? '';
                if (!$name || !$start || !$end) $error = 'All fields required';
                else { Season::create(['name'=>$name,'start_date'=>$start,'end_date'=>$end]); $ok='Season created'; }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/seasons/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

