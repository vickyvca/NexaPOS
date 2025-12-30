
<?php

require __DIR__ . '/../src/bootstrap.php';

// Enable error display in development to avoid blank pages
try {
    if (($config['app']['env'] ?? 'development') === 'development') {
        @ini_set('display_errors', '1');
        @ini_set('display_startup_errors', '1');
        @error_reporting(E_ALL);
    }
} catch (Throwable $e) { /* ignore */ }

// Simple router based on '?page=' query parameter
$route = $_GET['page'] ?? 'dashboard';

// Normalize: strip trailing .php from route and redirect to canonical
if (is_string($route) && substr($route, -4) === '.php') {
    $normalized = substr($route, 0, -4);
    header('Location: ' . base_url($normalized));
    exit;
}

// If app not installed, redirect to installer (except when already on installer or API)
try {
    $pdo_boot = get_pdo($config);
    $installed_flag = false;
    $has_settings = $pdo_boot->query("SHOW TABLES LIKE 'settings'")->fetchColumn();
    $has_users = $pdo_boot->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    $users_count = 0;
    if ($has_users) { $users_count = (int)$pdo_boot->query('SELECT COUNT(*) FROM users')->fetchColumn(); }
    // Consider installed if either settings table exists OR users table has rows
    $installed_flag = (bool)$has_settings || ($users_count > 0);
    if (!$installed_flag && $route !== 'install' && strpos($route, 'api/') !== 0) {
        header('Location: ' . base_url('install'));
        exit;
    }
} catch (Exception $e) {
    // If DB connection fails, allow installer
    if ($route !== 'install' && strpos($route, 'api/') !== 0) {
        header('Location: ' . base_url('install'));
        exit;
    }
}

// Whitelist of allowed pages
$pages = [
    'dashboard',
    'login',
    'logout',
    'pos',
    'orders',
    'receipt',
    'kitchen',
    'queue-display',
    'end_of_day',
    'order-slip',
    'tables',
    'transactions',
    'inventory_menus',      // <-- ADDED
    'menu_recipe',          // <-- ADDED
    'menus',                // <-- ADDED
    'addon_groups',        // <-- ADDED
    'addons',              // <-- ADDED
    'inventory/materials',
    'inventory/stock',
    'purchasing_receive',   // <-- ADDED
    'purchasing/po',
    'purchasing/suppliers',
    'accounting/cashbook',
    'shift/start',
    'accounting/journals',
    'hr/employees',
    'hr/attendance',
    'hr/kiosk',
    'settings',
    'users',
    'branches',
    'branch_settings',
    'install',
    'admin/settings/payment_mappings',
];

// Check if the requested route is a valid page
if (in_array($route, $pages)) {
    if (strpos($route, 'admin/') === 0) {
        $page_file = __DIR__ . '/../public/' . $route . '.php';
    } else {
        $page_file = __DIR__ . '/../templates/pages/' . $route . '.php';
    }
    if (file_exists($page_file)) {
        // The page file acts as a controller, setting up data and a $viewPath
        require $page_file;

        // If the controller defined a view, render it within the main layout
        if (isset($viewPath) && file_exists($viewPath)) {
            require __DIR__ . '/../templates/layout.php';
        } elseif (!isset($viewPath)) {
            // This can happen if the page file handles its own output (e.g. redirect, json)
            // or if it's a logic error. For now, we assume it's intentional.
        } else {
            http_response_code(500);
            die('Server Error - View file not found for route: ' . htmlspecialchars($route));
        }
    } else {
        http_response_code(404);
        die('404 Not Found - Page file missing for route: ' . htmlspecialchars($route));
    }
} else {
    // Handle API endpoints or other special routes
    if (strpos($route, 'api/') === 0) {
        $api_file = __DIR__ . '/../' . $route . '.php'; // API files are in root/api/
        if (file_exists($api_file)) {
            require $api_file;
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'API endpoint not found']);
        }
    } else {
        http_response_code(404);
        die('404 Not Found');
    }
}
