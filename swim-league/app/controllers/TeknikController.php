<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/SwolfTarget.php';

class TeknikController {
    public static function index() {
        require_login();
        $user = current_user();
        $ath = Athlete::findByUserId($user['id']);
        $pl = [];$st=[];$trend=[];
        if ($ath) {
            // Handle save targets
            if ($_SERVER['REQUEST_METHOD']==='POST') {
                if (!verify_csrf($_POST['csrf'] ?? '')) {
                    $error = 'Token CSRF tidak valid';
                } else {
                    $strokes = ['FREE','BACK','BREAST','FLY','IM'];
                    foreach ($strokes as $stt) {
                        $val = isset($_POST['target_'.$stt]) ? (int)$_POST['target_'.$stt] : 0;
                        if ($val > 0) {
                            SwolfTarget::upsert((int)$ath['id'], $stt, $val);
                        }
                    }
                    $ok = 'Target SWOLF tersimpan';
                }
            }
            $pl = DB::fetchAll('SELECT pool_length_m pl, ROUND(AVG(NULLIF(swolf,0))) avg_swolf, COUNT(*) c FROM activities WHERE athlete_id=? AND pool_length_m IS NOT NULL AND pool_length_m>0 AND swolf IS NOT NULL AND swolf>0 GROUP BY pl ORDER BY pl', [$ath['id']]);
            $st = DB::fetchAll('SELECT stroke_type st, ROUND(AVG(NULLIF(swolf,0))) avg_swolf, COUNT(*) c FROM activities WHERE athlete_id=? AND stroke_type IS NOT NULL AND stroke_type<>"" AND swolf IS NOT NULL AND swolf>0 GROUP BY st ORDER BY st', [$ath['id']]);
            $trend = DB::fetchAll('SELECT activity_on d, AVG(NULLIF(swolf,0)) avg_swolf FROM activities WHERE athlete_id=? AND swolf IS NOT NULL AND swolf>0 AND activity_on>=DATE_SUB(CURDATE(), INTERVAL 60 DAY) GROUP BY d ORDER BY d', [$ath['id']]);
            $targetsRaw = SwolfTarget::listByAthlete((int)$ath['id']);
            $targets = [];
            foreach ($targetsRaw as $t) { $targets[$t['stroke_type']] = (int)$t['target_swolf']; }
            // Build target series aligned with $st
            $target_series = [];
            foreach ($st as $row) { $target_series[] = isset($targets[$row['st']]) ? (int)$targets[$row['st']] : null; }
            // Alerts: rata-rata 30 hari vs target
            $st30 = DB::fetchAll('SELECT stroke_type st, ROUND(AVG(NULLIF(swolf,0))) avg_swolf FROM activities WHERE athlete_id=? AND stroke_type IS NOT NULL AND stroke_type<>"" AND swolf IS NOT NULL AND swolf>0 AND activity_on>=DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY st', [$ath['id']]);
            $alerts = [];
            foreach ($st30 as $row) {
                $stt = $row['st'];
                if (!empty($targets[$stt]) && (int)$row['avg_swolf'] > (int)$targets[$stt]) {
                    $alerts[] = [
                        'stroke' => $stt,
                        'avg' => (int)$row['avg_swolf'],
                        'target' => (int)$targets[$stt],
                    ];
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/teknik/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
