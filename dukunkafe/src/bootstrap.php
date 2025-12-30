<?php
declare(strict_types=1);

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Load Configuration (prefer local override if present)
$configPathLocal = __DIR__ . '/../config_local.php';
$configPathDefault = __DIR__ . '/../config.php';
$config = require (is_readable($configPathLocal) ? $configPathLocal : $configPathDefault);

// Database Connection
function get_pdo(array $config = null): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if ($config === null) {
            $config = require __DIR__ . '/../config.php';
        }

        $db_config = $config['database'];
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $db_config['user'], $db_config['password'], $options);
        } catch (\PDOException $e) {
            // In development, show error. In production, log and show generic message.
            if ($config['app']['env'] === 'development') {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            } else {
                // Here you would log the error to a file
                die('Could not connect to the database.');
            }
        }
    }

    return $pdo;
}

// --- Helper Functions ---

function base_url(string $path_and_query = ''): string
{
    $base = $_SERVER['SCRIPT_NAME']; // e.g., /dukunkafe/public/index.php
    if (empty($path_and_query)) {
        return $base;
    }
    
    $parts = explode('?', $path_and_query, 2);
    $page = $parts[0];
    $query = $parts[1] ?? '';

    if (!empty($query)) {
        return $base . '?page=' . $page . '&' . $query;
    } else {
        return $base . '?page=' . $page;
    }
}

// Build URL to public assets (e.g., uploads) regardless of app subfolder
function asset_url(string $path): string
{
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // e.g., /dukunkafe/public
    $clean = ltrim($path, '/');
    if ($baseDir === '' || $baseDir === '/') {
        return '/' . $clean;
    }
    return $baseDir . '/' . $clean;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

function view(string $template, array $data = []): void
{
    // Extract data to variables
    extract($data);

    // The path to the actual view file, which ends in .view.php
    $viewPath = __DIR__ . '/../templates/pages/' . $template . '.view.php';
    
    // The main layout file
    $layoutPath = __DIR__ . '/../templates/layout.php';

    if (!file_exists($viewPath)) {
        http_response_code(404);
        die("View not found: {$template}");
    }

    // The layout will include the view
    require $layoutPath;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_auth(array $roles = []): void
{
    if (!is_logged_in()) {
        redirect(base_url('login'));
    }

    if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        die('403 Forbidden - You do not have permission to access this page.');
    }

    // Branch protection: for non-admin/manager, lock session branch to user's branch if available
    $role = $_SESSION['user']['role'] ?? null;
    if (!in_array($role, ['admin','manager'])) {
        if (!empty($_SESSION['user']['branch_id'])) {
            set_current_branch_id((int)$_SESSION['user']['branch_id']);
        }
    }
}

function get_user_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

// --- Branch helpers (multi-cabang) ---
function get_current_branch_id(): int
{
    return isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;
}

function set_current_branch_id(int $id): void
{
    $_SESSION['branch_id'] = $id;
}

// Load settings with branch override (branch:{id}:{key} overrides base {key})
function load_settings(PDO $pdo): array
{
    $base = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $branch_id = get_current_branch_id();
    try {
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` LIKE ?");
        $stmt->execute(['branch:' . $branch_id . ':%']);
        $overrides = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($overrides as $k => $v) {
            $parts = explode(':', $k, 3); // ['branch', '{id}', '{key}']
            if (count($parts) === 3) {
                $base[$parts[2]] = $v;
            }
        }
    } catch (Exception $e) {}
    return $base;
}

function nav_link(string $route, string $icon, string $text): string
{
    $current_route = $_GET['page'] ?? 'dashboard';
    $is_active = ($current_route === $route);
    
    $base_classes = 'flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-colors duration-150';
    $active_classes = 'bg-brand-500 text-white shadow-lg';
    $inactive_classes = 'text-brand-800 hover:bg-brand-100 hover:text-brand-900';
    
    $classes = $base_classes . ' ' . ($is_active ? $active_classes : $inactive_classes);
    $url = base_url($route);

    return <<<HTML
    <a href="{$url}" class="{$classes}">
        <i class="{$icon} w-6 h-6 mr-3 flex items-center justify-center"></i>
        <span>{$text}</span>
    </a>
HTML;
}
