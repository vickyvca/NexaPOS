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
    $tgl_mulai = $input['tgl_mulai'] ?? null;
    $tgl_selesai = $input['tgl_selesai'] ?? null;
    $jenis_cuti = $input['jenis_cuti'] ?? null;
    $keterangan = $input['keterangan'] ?? null;
    $partner = $input['partner'] ?? '';

    if (!$tgl_mulai || !$tgl_selesai || !$jenis_cuti) {
        json_error('Mohon lengkapi semua field yang diperlukan.', 400);
    }
    if ($jenis_cuti === 'LIBUR' && !$partner) {
        json_error('Pilih partner yang akan menggantikan saat libur.', 400);
    }

    try {
        if ($partner) {
            $keterangan = '[PARTNER:' . $partner . '] ' . (string)$keterangan;
        }
        $___start = new DateTime($tgl_mulai);
        $___end = new DateTime($tgl_selesai);
        $___full = false;
        $___day = null;
        try {
            while ($___start <= $___end) {
                $___d = $___start->format('Y-m-d');
                $___q = $conn->prepare("SELECT COUNT(*) FROM T_PENGAJUAN_LIBUR WHERE STATUS='APPROVED' AND TGL_MULAI <= :d1 AND TGL_SELESAI >= :d2");
                $___q->execute([':d1' => $___d, ':d2' => $___d]);
                if ((int)$___q->fetchColumn() >= 2) {
                    $___full = true;
                    $___day = $___d;
                    break;
                }
                $___start->modify('+1 day');
            }
        } catch (PDOException $e) {
        }
        if ($___full) {
            json_error('Kuota libur tanggal ' . date('d/m/Y', strtotime($___day)) . ' sudah penuh (2 orang). Silakan pilih tanggal lain.', 400);
        }

        $sql = "INSERT INTO T_PENGAJUAN_LIBUR (KODESL, TGL_PENGAJUAN, TGL_MULAI, TGL_SELESAI, JENIS_CUTI, KETERANGAN, STATUS)
                VALUES (:nik, GETDATE(), :tgl_mulai, :tgl_selesai, :jenis, :ket, 'PENDING')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nik' => $nik,
            ':tgl_mulai' => $tgl_mulai,
            ':tgl_selesai' => $tgl_selesai,
            ':jenis' => $jenis_cuti,
            ':ket' => $keterangan,
        ]);

        $admins = get_admin_wa_list();
        if (!empty($admins)) {
            $nama = $nik;
            try {
                $stn = $conn->prepare("SELECT NAMASL FROM T_SALES WHERE KODESL=:nik");
                $stn->execute([':nik' => $nik]);
                $n = $stn->fetchColumn();
                if ($n) {
                    $nama = $n;
                }
            } catch (Exception $e) {
            }
            $msg = wa_tpl_leave_submitted_to_admin($nama, $nik, strtoupper($jenis_cuti), $tgl_mulai, $tgl_selesai, $partner ?: null, (string)($input['keterangan'] ?? ''));
            foreach ($admins as $wa) {
                kirimWATeksFonnte($wa, $msg);
            }
        }

        json_ok(['message' => 'Pengajuan berhasil dikirim! Menunggu persetujuan Admin.']);
    } catch (PDOException $e) {
        json_error('Gagal mengirim pengajuan: ' . $e->getMessage(), 500);
    }
}

$requests = [];
try {
    $stmt = $conn->prepare("SELECT * FROM T_PENGAJUAN_LIBUR WHERE KODESL = :nik ORDER BY CREATED_AT DESC");
    $stmt->execute([':nik' => $nik]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
}

$booked_dates_with_names = [];
try {
    $sql_libur = "SELECT p.TGL_MULAI, p.TGL_SELESAI, s.NAMASL
                  FROM T_PENGAJUAN_LIBUR p
                  JOIN T_SALES s ON p.KODESL = s.KODESL
                  WHERE p.STATUS = 'APPROVED'";
    $stmt_libur = $conn->prepare($sql_libur);
    $stmt_libur->execute();
    $results = $stmt_libur->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $start = new DateTime($row['TGL_MULAI']);
        $end = new DateTime($row['TGL_SELESAI']);
        $end->modify('+1 day');
        $nama_pegawai = trim($row['NAMASL']);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            if (!isset($booked_dates_with_names[$date_str])) {
                $booked_dates_with_names[$date_str] = [];
            }
            $booked_dates_with_names[$date_str][] = $nama_pegawai;
        }
    }
} catch (PDOException $e) {
}

$selected = get_selected_employees();
$partners = [];
foreach ($selected as $emp) {
    if (($emp['nik'] ?? '') === $nik) {
        continue;
    }
    $partners[] = [
        'nik' => $emp['nik'] ?? '',
        'nama' => $emp['nama'] ?? $emp['nik'],
    ];
}

json_ok([
    'data' => [
        'requests' => array_map(function ($row) {
            return [
                'id' => $row['ID'] ?? null,
                'nik' => $row['KODESL'] ?? '',
                'tgl_mulai' => $row['TGL_MULAI'] ?? null,
                'tgl_selesai' => $row['TGL_SELESAI'] ?? null,
                'jenis' => $row['JENIS_CUTI'] ?? '',
                'keterangan' => $row['KETERANGAN'] ?? '',
                'status' => $row['STATUS'] ?? '',
                'created_at' => $row['CREATED_AT'] ?? null,
            ];
        }, $requests),
        'booked' => $booked_dates_with_names,
        'partners' => $partners,
    ],
]);
