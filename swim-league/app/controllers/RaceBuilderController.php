<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/SwimEvent.php';

class RaceBuilderController {
    public static function index() {
        require_login();
        require_role(['admin','organizer']);
        $races = DB::fetchAll('SELECT rc.*, m.name AS meet_name, m.meet_on, se.name AS swim_event
            FROM races rc JOIN meets m ON rc.meet_id=m.id JOIN swim_events se ON rc.swim_event_id=se.id
            ORDER BY m.meet_on DESC, rc.id DESC');
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/races/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function create() {
        require_login();
        require_role(['admin','organizer']);
        $ok=null; $error=null;
        $meets = DB::fetchAll('SELECT id, name, meet_on FROM meets ORDER BY meet_on DESC');
        $swimEvents = SwimEvent::all();
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) $error='Invalid CSRF token';
            else {
                $meet_id = (int)($_POST['meet_id'] ?? 0);
                $relay_type = trim($_POST['relay_type'] ?? '');
                $swim_event_id = 0;
                if ($relay_type !== '') {
                    // Map relay to stroke+distance
                    $map = [
                        '4x50_free' => ['distance'=>200, 'stroke'=>'FREE_RELAY', 'name'=>'4x50m Freestyle Relay'],
                        '4x100_free' => ['distance'=>400, 'stroke'=>'FREE_RELAY', 'name'=>'4x100m Freestyle Relay'],
                        '4x200_free' => ['distance'=>800, 'stroke'=>'FREE_RELAY', 'name'=>'4x200m Freestyle Relay'],
                        '4x100_medley' => ['distance'=>400, 'stroke'=>'MEDLEY_RELAY', 'name'=>'4x100m Medley Relay'],
                    ];
                    if (isset($map[$relay_type])) {
                        $cfg = $map[$relay_type];
                        $swim_event_id = SwimEvent::ensure((int)$cfg['distance'], $cfg['stroke'], $cfg['name'], 'X');
                    } else {
                        $error = 'Unknown relay type';
                    }
                } else {
                    $stroke = trim($_POST['stroke'] ?? 'FREE');
                    $distance_m = (int)($_POST['distance_m'] ?? 0);
                    $gender = in_array(($_POST['gender'] ?? 'X'), ['M','F','X'], true) ? $_POST['gender'] : 'X';
                    // Validation rules: IM only 200/400; 800/1500 only FREE
                    if ($stroke === 'IM' && !in_array($distance_m, [200,400], true)) {
                        $error = 'IM allowed only for 200m or 400m';
                    }
                    if (in_array($distance_m, [800,1500], true) && $stroke !== 'FREE') {
                        $error = '800/1500 are Freestyle only';
                    }
                    if (!$error && $distance_m && $stroke) {
                        $name = $distance_m . 'm ' . $stroke . ($gender!=='X' ? ' ' . $gender : '');
                        $swim_event_id = SwimEvent::ensure($distance_m, $stroke, $name, $gender);
                    }
                }
                $round_name = trim($_POST['round_name'] ?? 'Final');
                $age_group = $_POST['age_group'] ?? '';
                if ($age_group) { $round_name .= ' ' . $age_group; }
                $heat_no = (int)($_POST['heat_no'] ?? 1);
                if (!$meet_id || !$swim_event_id) $error='Meet and event required';
                else {
                    DB::exec('INSERT INTO races(meet_id,swim_event_id,round_name,heat_no) VALUES (?,?,?,?)', [$meet_id,$swim_event_id,$round_name,$heat_no]);
                    $ok = 'Race created';
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/races/create.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}
