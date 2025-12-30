<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

function get_sales_name(PDO $conn, string $nik): string {
    try {
        $stmt = $conn->prepare("SELECT TOP 1 NAMASL FROM T_SALES WHERE KODESL = :nik");
        $stmt->execute([':nik' => $nik]);
        return $stmt->fetchColumn() ?: $nik;
    } catch (PDOException $e) {
        return $nik;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }
    $action = $input['action'] ?? null;
    $id = $input['id'] ?? null;
    $new_status = $input['new_status'] ?? null;
    $admin_nik = $_SESSION['nik'] ?? 'ADMIN';

    if ($action === 'update_status' && $id && $new_status && in_array($new_status, ['APPROVED', 'REJECTED'], true)) {
        try {
            $conn->beginTransaction();
            $sql = "UPDATE T_PENGAJUAN_LIBUR SET STATUS = :status, TGL_PERSETUJUAN = GETDATE(), ADMIN_NIK = :admin_nik WHERE ID = :id AND STATUS = 'PENDING'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':status' => $new_status, ':admin_nik' => $admin_nik, ':id' => $id]);
            $status_updated = $stmt->rowCount() > 0;

            $detail = null;
            if ($status_updated && $new_status === 'APPROVED') {
                $stmt_detail = $conn->prepare("SELECT KODESL, TGL_MULAI, TGL_SELESAI, JENIS_CUTI, KETERANGAN FROM T_PENGAJUAN_LIBUR WHERE ID = ?");
                $stmt_detail->execute([$id]);
                $detail = $stmt_detail->fetch(PDO::FETCH_ASSOC);

                if ($detail) {
                    $kodesl = $detail['KODESL'];
                    $tgl_mulai = new DateTime($detail['TGL_MULAI']);
                    $tgl_selesai = new DateTime($detail['TGL_SELESAI']);
                    $jenis_cuti = $detail['JENIS_CUTI'];

                    $current_date_chk = clone $tgl_mulai;
                    $violates = false;
                    $violated_day = null;
                    while ($current_date_chk <= $tgl_selesai) {
                        $dkey = $current_date_chk->format('Y-m-d');
                        $qcnt = $conn->prepare("SELECT COUNT(*) FROM T_PENGAJUAN_LIBUR WHERE STATUS='APPROVED' AND TGL_MULAI <= :d1 AND TGL_SELESAI >= :d2");
                        $qcnt->execute([':d1' => $dkey, ':d2' => $dkey]);
                        $cnt = (int)$qcnt->fetchColumn();
                        if ($cnt > 2) {
                            $violates = true;
                            $violated_day = $dkey;
                            break;
                        }
                        $current_date_chk->modify('+1 day');
                    }
                    if ($violates) {
                        $stmt = $conn->prepare("UPDATE T_PENGAJUAN_LIBUR SET STATUS='REJECTED', TGL_PERSETUJUAN=GETDATE(), ADMIN_NIK=:admin WHERE ID=:id");
                        $stmt->execute([':admin' => $admin_nik, ':id' => $id]);
                        $conn->commit();
                        json_error('Pengajuan ditolak otomatis. Kuota libur tanggal ' . $violated_day . ' sudah penuh (2 orang).', 400);
                    }

                    $partner_nik = null;
                    if (!empty($detail['KETERANGAN']) && preg_match('/\\[PARTNER:([A-Za-z0-9_\\-]+)\\]/', $detail['KETERANGAN'], $m)) {
                        $partner_nik = $m[1];
                    }

                    $sql_batch = '';
                    $current_date = clone $tgl_mulai;
                    while ($current_date <= $tgl_selesai) {
                        $tgl_absen = $current_date->format('Y-m-d');
                        $q_kodesl = $conn->quote($kodesl);
                        $q_tgl = $conn->quote($tgl_absen);
                        $q_status = $conn->quote($jenis_cuti);

                        $sql_batch .= "IF EXISTS (SELECT 1 FROM T_ABSENSI WHERE KODESL = {$q_kodesl} AND TGL = {$q_tgl}) 
                                          BEGIN 
                                            UPDATE T_ABSENSI 
                                              SET STATUS_HARI = {$q_status}, 
                                                  SHIFT_MASUK = NULL, 
                                                  SHIFT_PULANG = NULL, 
                                                  OVERTIME_BONUS_FLAG = 0 
                                              WHERE KODESL = {$q_kodesl} AND TGL = {$q_tgl}; 
                                          END 
                                          ELSE 
                                          BEGIN 
                                            INSERT INTO T_ABSENSI (KODESL, TGL, STATUS_HARI, SHIFT_JADWAL) 
                                            VALUES ({$q_kodesl}, {$q_tgl}, {$q_status}, 'S1'); 
                                          END; ";

                        if ($partner_nik) {
                            $q_partner = $conn->quote($partner_nik);
                            $sql_batch .= "IF EXISTS (SELECT 1 FROM T_ABSENSI WHERE KODESL = {$q_partner} AND TGL = {$q_tgl}) 
                                             BEGIN 
                                                UPDATE T_ABSENSI 
                                                   SET OVERTIME_BONUS_FLAG = 1,
                                                       OVERTIME_NOTES = CONCAT(ISNULL(OVERTIME_NOTES,''), CASE WHEN ISNULL(OVERTIME_NOTES,'')='' THEN '' ELSE ' | ' END, 'OT_PARTNER')
                                                 WHERE KODESL = {$q_partner} AND TGL = {$q_tgl};
                                             END 
                                             ELSE 
                                             BEGIN 
                                                INSERT INTO T_ABSENSI (KODESL, TGL, STATUS_HARI, SHIFT_JADWAL, OVERTIME_BONUS_FLAG, OVERTIME_NOTES)
                                                VALUES ({$q_partner}, {$q_tgl}, 'HADIR', 'S1', 1, 'OT_PARTNER');
                                             END; ";
                        }

                        $current_date->modify('+1 day');
                    }

                    if (!empty($sql_batch)) {
                        $conn->exec($sql_batch);
                    }
                }
            }

            $conn->commit();
            json_ok(['message' => 'Pengajuan diproses.']);
        } catch (PDOException $e) {
            $conn->rollBack();
            json_error('Gagal memproses pengajuan: ' . $e->getMessage(), 500);
        }
    }

    if ($action === 'delete' && $id) {
        try {
            $stmtDel = $conn->prepare("DELETE FROM T_PENGAJUAN_LIBUR WHERE ID = :id");
            $stmtDel->execute([':id' => $id]);
            json_ok(['message' => 'Pengajuan dihapus.']);
        } catch (PDOException $e) {
            json_error('Gagal menghapus: ' . $e->getMessage(), 500);
        }
    }

    json_error('Aksi tidak valid.', 400);
}

$filter = $_GET['filter'] ?? 'PENDING';
$sql_filter = $filter === 'ALL' ? '' : "WHERE TPL.STATUS = :filter";
$sql = "
    SELECT 
        TPL.*,
        TS.NAMASL 
    FROM T_PENGAJUAN_LIBUR TPL
    LEFT JOIN T_SALES TS ON TPL.KODESL = TS.KODESL
    {$sql_filter}
    ORDER BY TPL.CREATED_AT DESC
";
$stmt = $conn->prepare($sql);
if ($filter !== 'ALL') {
    $stmt->execute([':filter' => $filter]);
} else {
    $stmt->execute();
}
$pengajuan_list = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

json_ok([
    'data' => [
        'filter' => $filter,
        'rows' => array_map(function ($p) {
            $tgl_mulai = new DateTime($p['TGL_MULAI']);
            $tgl_selesai = new DateTime($p['TGL_SELESAI']);
            $durasi = $tgl_mulai->diff($tgl_selesai)->days + 1;
            return [
                'id' => $p['ID'] ?? null,
                'nik' => $p['KODESL'] ?? '',
                'nama' => $p['NAMASL'] ?? '',
                'jenis' => $p['JENIS_CUTI'] ?? '',
                'tgl_mulai' => $p['TGL_MULAI'] ?? null,
                'tgl_selesai' => $p['TGL_SELESAI'] ?? null,
                'durasi' => $durasi,
                'keterangan' => $p['KETERANGAN'] ?? '',
                'created_at' => $p['CREATED_AT'] ?? null,
                'status' => $p['STATUS'] ?? '',
            ];
        }, $pengajuan_list),
    ],
]);
