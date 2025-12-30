<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../models/Athlete.php';
require_once __DIR__ . '/../models/Club.php';

class ProfileController {
    public static function index() {
        require_login();
        $user = current_user();
        $athlete = Athlete::findByUserId($user['id']);
        $clubs = Club::all();
        $ok = null; $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else {
                // Only athlete can edit their athlete profile details (or admins too)
                if ($user['role'] === 'athlete' || $user['role'] === 'admin') {
                    $data = [
                        'club_id' => isset($_POST['club_id']) && $_POST['club_id'] !== '' ? (int)$_POST['club_id'] : null,
                        'gender' => $_POST['gender'] ?? 'M',
                        'birthdate' => $_POST['birthdate'] ?? date('Y-m-d'),
                    ];
                    $res = Athlete::upsertForUser((int)$user['id'], $data);
                    if ($res['ok']) { $ok = 'Profile saved'; $athlete = Athlete::findByUserId($user['id']); }
                    else $error = 'Failed to save profile';
                } else {
                    $error = 'Not allowed';
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/_layout/nav.php';
        include __DIR__ . '/../views/profile/index.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }
}

