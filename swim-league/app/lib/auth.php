<?php
require_once __DIR__ . '/db.php';

function start_session_once() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string {
    start_session_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token): bool {
    start_session_once();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function current_user(): ?array {
    start_session_once();
    if (!empty($_SESSION['user_id'])) {
        return DB::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }
    return null;
}

function is_role(string $role): bool {
    $u = current_user();
    return $u && $u['role'] === $role;
}

function has_role(array $roles): bool {
    $u = current_user();
    return $u && in_array($u['role'], $roles, true);
}

function require_role(array $roles) {
    if (!has_role($roles)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function require_login() {
    if (!current_user()) {
        header('Location: ' . BASE_URL . '/public/index.php?p=login');
        exit;
    }
}

function login(string $email, string $password): bool {
    $user = DB::fetch('SELECT * FROM users WHERE email = ?', [$email]);
    if ($user && password_verify($password, $user['password_hash'])) {
        start_session_once();
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function logout() {
    start_session_once();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function register_user(string $name, string $email, string $password, string $role = 'athlete'): array {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email'];
    }
    $exists = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
    if ($exists) {
        return ['ok' => false, 'error' => 'Email already used'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    DB::exec('INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?)', [$name, $email, $hash, $role]);
    return ['ok' => true, 'id' => DB::lastId()];
}
