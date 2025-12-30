<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/util.php';

class AuthController {
    public static function login() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                if (login($email, $password)) {
                    header('Location: ' . BASE_URL . '/public/index.php?p=dashboard');
                    exit;
                } else {
                    $error = 'Invalid email or password';
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/auth/login.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function register() {
        $error = null; $ok = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf'] ?? '')) {
                $error = 'Invalid CSRF token';
            } else {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = 'athlete';
                $res = register_user($name, $email, $password, $role);
                if ($res['ok']) {
                    $ok = 'Registered. Please login.';
                } else {
                    $error = $res['error'] ?? 'Register failed';
                }
            }
        }
        include __DIR__ . '/../views/_layout/header.php';
        include __DIR__ . '/../views/auth/register.php';
        include __DIR__ . '/../views/_layout/footer.php';
    }

    public static function logout() {
        logout();
        header('Location: ' . BASE_URL . '/public/index.php?p=login');
        exit;
    }
}

