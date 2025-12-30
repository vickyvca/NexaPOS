<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Activity.php';

class DashboardController {
    public static function index() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $summary = ['dist'=>0,'best_pace100'=>null];
        $stats = ['month_dist'=>0,'week_dist'=>0,'month_duration'=>0,'month_count'=>0,'best_pace100'=>null];
        $latest = [];
        if ($ath) {
            $summary = Activity::monthSummary($ath['id']);
            $stats = Activity::stats($ath['id']);
            $latest = Activity::latestByAthlete($ath['id'], 10);
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/dashboard/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
