<?php
require_once __DIR__ . '/api_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode tidak valid.', 405);
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$role = strtolower(trim($input['role'] ?? ''));
$password = (string)($input['password'] ?? $input['pass'] ?? '');
$identifier = trim((string)($input['identifier'] ?? $input['nik'] ?? $input['kodesp'] ?? ''));

if (!$role || !$identifier || $password === '') {
    json_error('Role, user, dan password wajib diisi.');
}

try {
    if ($role === 'supplier') {
        $stmt = $conn->prepare("SELECT KODESP, NAMASP FROM T_SUPLIER WHERE KODESP = :kodesp AND PASS = :pass");
        $stmt->execute(['kodesp' => $identifier, 'pass' => $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            json_error('Kode supplier atau password salah.', 401);
        }

        $_SESSION['kodesp'] = $user['KODESP'];
        $_SESSION['role'] = 'supplier';
        $_SESSION['namasp'] = trim($user['NAMASP'] ?? '') ?: 'Supplier';

        json_ok([
            'user' => [
                'id' => $user['KODESP'],
                'name' => $_SESSION['namasp'],
                'role' => 'supplier',
            ],
        ]);
    }

    if ($role === 'admin' || $role === 'pegawai') {
        $stmt = $conn->prepare("SELECT NIK, PASS FROM [LOGIN ANDROID] WHERE NIK = :nik AND PASS = :pass");
        $stmt->execute(['nik' => $identifier, 'pass' => $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            json_error('NIK atau password salah.', 401);
        }

        $is_admin = ($identifier === '225');
        $resolved_role = $is_admin ? 'admin' : 'pegawai';

        if ($role !== $resolved_role) {
            json_error('Role tidak sesuai dengan akun.', 403);
        }

        $_SESSION['nik'] = $identifier;
        $_SESSION['role'] = $resolved_role;
        $_SESSION['is_admin'] = $is_admin;

        $default_list = ['123','1234','12345','123456','password','Password'];
        $is_default = ($password === $identifier) || in_array($password, $default_list, true);
        $_SESSION['must_change_password'] = ($resolved_role === 'pegawai' && $is_default);

        $nama = 'Pegawai';
        try {
            $stmt_nama = $conn->prepare("SELECT NAMASL FROM T_SALES WHERE KODESL = :nik");
            $stmt_nama->execute(['nik' => $identifier]);
            $r = $stmt_nama->fetch(PDO::FETCH_ASSOC);
            if (!empty($r['NAMASL'])) {
                $nama = trim($r['NAMASL']);
            }
        } catch (PDOException $e) {
            // fallback
        }

        $_SESSION['nama_pegawai'] = $nama;

        json_ok([
            'user' => [
                'id' => $identifier,
                'name' => $nama,
                'role' => $resolved_role,
                'mustChangePassword' => $_SESSION['must_change_password'] ? true : false,
            ],
        ]);
    }

    json_error('Role tidak dikenali.', 400);
} catch (PDOException $e) {
    json_error('Gagal login: ' . $e->getMessage(), 500);
}
