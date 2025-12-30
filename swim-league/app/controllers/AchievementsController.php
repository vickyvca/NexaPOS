<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Achievement.php';

class AchievementsController {
    public static function index() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $list = $ath ? Achievement::listByAthlete((int)$ath['id']) : [];
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/achievements/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function scan() {
        require_login();
        require_role(['admin','organizer']);
        $ok = null; $error = null; $report = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ensure codes
            $codes = [
                'FIRST_RACE' => 'First official race finished',
                'FIRST_PODIUM' => 'First time on podium',
                'SUB_60s_100_FREE' => '100m Freestyle under 60s',
                '10K_MONTH' => '10 km in a month',
            ];
            foreach ($codes as $c=>$n) { Achievement::ensure($c, $n); }

            $athletes = DB::fetchAll('SELECT id FROM athletes');
            $now = date('Y-m-d');
            $totalGranted = 0;
            foreach ($athletes as $a) {
                $aid = (int)$a['id'];
                // FIRST_RACE
                $hasRace = DB::fetch('SELECT MIN(r.id) mid FROM results r WHERE r.athlete_id=? AND r.status="OK"', [$aid]);
                if ($hasRace && $hasRace['mid']) { if (Achievement::grant($aid, 'FIRST_RACE', $now)) { $totalGranted++; $report[]="Athlete #$aid -> FIRST_RACE"; } }

                // FIRST_PODIUM (position <= 3 in any race)
                $athResults = DB::fetchAll('SELECT r.id, r.race_id, r.time_ms FROM results r WHERE r.athlete_id=? AND r.status="OK"', [$aid]);
                foreach ($athResults as $r) {
                    $faster = DB::fetch('SELECT COUNT(*) c FROM results WHERE race_id=? AND status="OK" AND time_ms < ?', [$r['race_id'], $r['time_ms']]);
                    $pos = 1 + (int)($faster['c'] ?? 0);
                    if ($pos <= 3) { if (Achievement::grant($aid, 'FIRST_PODIUM', $now)) { $totalGranted++; $report[]="Athlete #$aid -> FIRST_PODIUM"; } break; }
                }

                // SUB_60s_100_FREE
                $sub = DB::fetch('SELECT r.id FROM results r JOIN races rc ON r.race_id=rc.id JOIN swim_events se ON rc.swim_event_id=se.id WHERE r.athlete_id=? AND r.status="OK" AND se.distance_m=100 AND se.stroke="FREE" AND r.time_ms < 60000 LIMIT 1', [$aid]);
                if ($sub) { if (Achievement::grant($aid, 'SUB_60s_100_FREE', $now)) { $totalGranted++; $report[]="Athlete #$aid -> SUB_60s_100_FREE"; } }

                // 10K_MONTH (current month)
                $start = (new DateTime('first day of this month 00:00:00'))->format('Y-m-d');
                $sum = DB::fetch('SELECT COALESCE(SUM(distance_m),0) s FROM activities WHERE athlete_id=? AND activity_on>=?', [$aid, $start]);
                if ((int)($sum['s'] ?? 0) >= 10000) { if (Achievement::grant($aid, '10K_MONTH', $now)) { $totalGranted++; $report[]="Athlete #$aid -> 10K_MONTH"; } }
            }
            $ok = "Scan complete. Granted $totalGranted achievements.";
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/achievements/scan.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

