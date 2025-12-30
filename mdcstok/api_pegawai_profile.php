<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $action = $input['action'] ?? '';
    if ($action === 'update_wa') {
        $wa = preg_replace('/[^0-9]/', '', (string)($input['wa'] ?? ''));
        if ($wa === '') {
            json_error('Nomor WA wajib diisi.', 400);
        }
        if (!set_pegawai_wa($nik, $wa)) {
            json_error('Gagal menyimpan nomor WA.', 500);
        }
        json_ok(['message' => 'Nomor WA berhasil diperbarui.']);
    }

    if ($action === 'change_password') {
        $old = (string)($input['old'] ?? '');
        $new = (string)($input['new'] ?? '');
        $confirm = (string)($input['confirm'] ?? '');
        $wa = preg_replace('/[^0-9]/', '', (string)($input['wa'] ?? ''));
        $existing_wa = get_pegawai_wa($nik);

        if ($new !== $confirm) {
            json_error('Konfirmasi password tidak sama.', 400);
        }
        if (strlen($new) < 6) {
            json_error('Password baru minimal 6 karakter.', 400);
        }
        if ($new === $nik) {
            json_error('Password tidak boleh sama dengan NIK.', 400);
        }
        if (in_array(strtolower($new), ['123', '1234', '12345', '123456', 'password'], true)) {
            json_error('Password terlalu lemah. Gunakan kombinasi lain.', 400);
        }

        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM [LOGIN ANDROID] WHERE NIK = :nik AND PASS = :pass");
            $stmt->execute([':nik' => $nik, ':pass' => $old]);
            $ok = (int)$stmt->fetchColumn() > 0;
            if (!$ok) {
                json_error('Password saat ini salah.', 400);
            }
            $upd = $conn->prepare("UPDATE [LOGIN ANDROID] SET PASS = :new WHERE NIK = :nik");
            $upd->execute([':new' => $new, ':nik' => $nik]);
            unset($_SESSION['must_change_password']);
            if (!$existing_wa && $wa !== '') {
                set_pegawai_wa($nik, $wa);
            }
        } catch (PDOException $e) {
            json_error('DB Error: ' . $e->getMessage(), 500);
        }

        json_ok(['message' => 'Password berhasil diubah.']);
    }

    json_error('Aksi tidak dikenal.', 400);
}

$nama = $_SESSION['nama_pegawai'] ?? 'Pegawai';
if (!$nama || $nama === 'Pegawai') {
    try {
        $stmt = $conn->prepare("SELECT NAMASL FROM T_SALES WHERE KODESL = :nik");
        $stmt->execute([':nik' => $nik]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['NAMASL'])) {
            $nama = trim($row['NAMASL']);
        }
    } catch (PDOException $e) {
    }
}

json_ok([
    'data' => [
        'nik' => $nik,
        'nama' => $nama,
        'wa' => get_pegawai_wa($nik) ?: '',
        'mustChangePassword' => !empty($_SESSION['must_change_password']),
    ],
]);
