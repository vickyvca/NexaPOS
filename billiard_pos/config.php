<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'billiard_pos';
$db_user = 'root';
$db_pass = '';

date_default_timezone_set('Asia/Jakarta');

// Maintenance settings
$maintenance_password = 'admin123'; // ganti oleh admin
$maintenance_duration_minutes = 5; // durasi lampu maintenance

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
