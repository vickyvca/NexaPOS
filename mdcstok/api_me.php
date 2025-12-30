<?php
require_once __DIR__ . '/api_common.php';

if (empty($_SESSION['role'])) {
    json_error('Belum login.', 401);
}

$role = $_SESSION['role'];
if ($role === 'supplier') {
    json_ok([
        'user' => [
            'id' => $_SESSION['kodesp'] ?? '',
            'name' => $_SESSION['namasp'] ?? 'Supplier',
            'role' => 'supplier',
        ],
    ]);
}

json_ok([
    'user' => [
        'id' => $_SESSION['nik'] ?? '',
        'name' => $_SESSION['nama_pegawai'] ?? 'Pegawai',
        'role' => $role,
        'mustChangePassword' => !empty($_SESSION['must_change_password']),
    ],
]);
