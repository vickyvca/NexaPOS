<?php
require_once __DIR__ . '/api_common.php';

$role = $_SESSION['role'] ?? '';
$identifier = '';
if ($role === 'supplier') {
    $identifier = $_SESSION['kodesp'] ?? '';
} else {
    $identifier = $_SESSION['nik'] ?? '';
}

if ($identifier === '') {
    json_error('Akun tidak teridentifikasi.', 401);
}

$allowed_roles = ['supplier', 'pegawai', 'admin'];
if (!in_array($role, $allowed_roles, true)) {
    json_error('Akses ditolak.', 401);
}

$storage_dir = PAYROLL_DATA_DIR;
@mkdir($storage_dir, 0777, true);
$storage_file = $storage_dir . '/push_tokens.json';
$data = [];
if (is_file($storage_file)) {
    $content = @file_get_contents($storage_file);
    $decoded = json_decode($content ?: '{}', true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

$group = $role;
$data[$group] = $data[$group] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = trim((string)($payload['token'] ?? ''));
    if ($token === '') {
        json_error('Token tidak boleh kosong.', 400);
    }
    $data[$group][$identifier] = $token;
    file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    json_ok(['data' => ['token' => $token]]);
}

$existing = $data[$group][$identifier] ?? '';
json_ok(['data' => ['token' => $existing]]);
