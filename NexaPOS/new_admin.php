<?php
// Jalankan sekali via browser: http://localhost/NexaPOS/new_admin.php
require_once __DIR__ . '/helpers.php';
$pdo = getPDO();
$username = 'admin';
$password = 'admin123';
$name = 'Admin';
$role = 'admin';

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
$stmt->execute([$username]);
$exist = $stmt->fetchColumn();
if ($exist) {
    $pdo->prepare("UPDATE users SET name=?, password_hash=?, role=? WHERE id=?")
        ->execute([$name, $hash, $role, $exist]);
    echo "Reset password untuk {$username} sukses";
} else {
    $pdo->prepare("INSERT INTO users(name, username, password_hash, role, created_at) VALUES(?,?,?,?,NOW())")
        ->execute([$name, $username, $hash, $role]);
    echo "User {$username} dibuat";
}
?>
