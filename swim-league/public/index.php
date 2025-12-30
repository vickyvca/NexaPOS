<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/util.php';

$p = $_GET['p'] ?? '';

switch ($p) {
    case 'login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::login();
        break;
    case 'register':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::register();
        break;
    case 'logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::logout();
        break;
    case 'dashboard':
        require_once __DIR__ . '/../app/controllers/DashboardController.php';
        DashboardController::index();
        break;
    case 'activities':
        require_once __DIR__ . '/../app/controllers/ActivityController.php';
        ActivityController::index();
        break;
    case 'activities-create':
        require_once __DIR__ . '/../app/controllers/ActivityController.php';
        ActivityController::create();
        break;
    case 'activities-import':
        require_once __DIR__ . '/../app/controllers/ActivityController.php';
        ActivityController::importCsv();
        break;
    case 'meets':
        require_once __DIR__ . '/../app/controllers/MeetController.php';
        require_once __DIR__ . '/../app/models/RaceEntry.php';
        MeetController::index();
        break;
    case 'meet-create':
        require_once __DIR__ . '/../app/controllers/MeetController.php';
        MeetController::create();
        break;
    case 'entries':
        require_once __DIR__ . '/../app/controllers/MeetController.php';
        require_once __DIR__ . '/../app/models/RaceEntry.php';
        MeetController::entries();
        break;
    case 'results-import':
        require_once __DIR__ . '/../app/controllers/MeetController.php';
        MeetController::importResultsCsv();
        break;
    case 'leaderboard':
        require_once __DIR__ . '/../app/controllers/LeaderboardController.php';
        LeaderboardController::index();
        break;
    case 'team':
        require_once __DIR__ . '/../app/controllers/TeamController.php';
        TeamController::index();
        break;
    case 'team-club':
        require_once __DIR__ . '/../app/controllers/TeamController.php';
        TeamController::club();
        break;
    case 'races':
        require_once __DIR__ . '/../app/controllers/RaceBuilderController.php';
        RaceBuilderController::index();
        break;
    case 'race-create':
        require_once __DIR__ . '/../app/controllers/RaceBuilderController.php';
        RaceBuilderController::create();
        break;
    case 'achievements':
        require_once __DIR__ . '/../app/controllers/AchievementsController.php';
        AchievementsController::index();
        break;
    case 'achievements-scan':
        require_once __DIR__ . '/../app/controllers/AchievementsController.php';
        AchievementsController::scan();
        break;
    case 'coach':
        require_once __DIR__ . '/../app/controllers/CoachController.php';
        CoachController::dashboard();
        break;
    case 'coach-programs':
        require_once __DIR__ . '/../app/controllers/CoachProgramController.php';
        CoachProgramController::index();
        break;
    case 'coach-program-create':
        require_once __DIR__ . '/../app/controllers/CoachProgramController.php';
        CoachProgramController::create();
        break;
    case 'workouts':
        require_once __DIR__ . '/../app/controllers/WorkoutController.php';
        WorkoutController::index();
        break;
    case 'workout-complete':
        require_once __DIR__ . '/../app/controllers/WorkoutController.php';
        WorkoutController::complete();
        break;
    case 'teknik':
        require_once __DIR__ . '/../app/controllers/TeknikController.php';
        TeknikController::index();
        break;
    case 'standings':
        require_once __DIR__ . '/../app/controllers/StandingsController.php';
        StandingsController::index();
        break;
    case 'standings-export':
        require_once __DIR__ . '/../app/controllers/StandingsController.php';
        StandingsController::exportCsv();
        break;
    case 'seasons':
        require_once __DIR__ . '/../app/controllers/SeasonController.php';
        SeasonController::index();
        break;
    case 'season-create':
        require_once __DIR__ . '/../app/controllers/SeasonController.php';
        SeasonController::create();
        break;
    case 'events':
        require_once __DIR__ . '/../app/controllers/EventController.php';
        EventController::index();
        break;
    case 'event-create':
        require_once __DIR__ . '/../app/controllers/EventController.php';
        EventController::create();
        break;
    case 'profile':
        require_once __DIR__ . '/../app/controllers/ProfileController.php';
        ProfileController::index();
        break;
    case 'api-activities':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        ApiController::activities();
        break;
    case 'api-leaderboards':
        require_once __DIR__ . '/../app/controllers/ApiController.php';
        ApiController::leaderboards();
        break;
    default:
        if (current_user()) {
            header('Location: ' . BASE_URL . '/public/index.php?p=dashboard');
            exit;
        } else {
            header('Location: ' . BASE_URL . '/public/index.php?p=login');
            exit;
        }
}
