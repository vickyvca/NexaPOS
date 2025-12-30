<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

$data_file = __DIR__ . '/payroll/data/shift_requests.json';
if (!is_dir(dirname($data_file))) {
    @mkdir(dirname($data_file), 0777, true);
}
if (!is_file($data_file)) {
    file_put_contents($data_file, json_encode([], JSON_PRETTY_PRINT));
}

function load_shift_requests(string $file): array {
    $js = json_decode(@file_get_contents($file), true);
    return is_array($js) ? $js : [];
}
function save_shift_requests(string $file, array $rows): void {
    file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }
    $action = $input['action'] ?? null;
    $id = $input['id'] ?? null;

    if (!$action || !$id) {
        json_error('Aksi atau ID tidak valid.', 400);
    }

    $rows = load_shift_requests($data_file);
    foreach ($rows as &$r) {
        if (($r['id'] ?? '') === $id) {
            if ($action === 'approve' && ($r['status'] ?? '') === 'PENDING') {
                $date = $r['date'];
                $nikA = $r['requester'];
                $nikB = $r['partner'];

                $getShift = function ($nik) use ($conn, $date) {
                    try {
                        $q = $conn->prepare("SELECT SHIFT_JADWAL FROM T_ABSENSI WHERE KODESL=:nik AND TGL=:tgl");
                        $q->execute([':nik' => $nik, ':tgl' => $date]);
                        return $q->fetchColumn();
                    } catch (Exception $e) {
                        return null;
                    }
                };
                $setShift = function ($nik, $shift) use ($conn, $date) {
                    try {
                        $sql = "IF EXISTS (SELECT 1 FROM T_ABSENSI WHERE KODESL = :nik AND TGL = :tgl)
                                  UPDATE T_ABSENSI SET SHIFT_JADWAL = :s, OVERTIME_BONUS_FLAG = 0 WHERE KODESL = :nik AND TGL = :tgl
                                ELSE
                                  INSERT INTO T_ABSENSI (KODESL, TGL, STATUS_HARI, SHIFT_JADWAL) VALUES (:nik, :tgl, 'HADIR', :s)";
                        $st = $conn->prepare($sql);
                        $st->execute([':nik' => $nik, ':tgl' => $date, ':s' => $shift ?: 'S1']);
                    } catch (Exception $e) {
                    }
                };
                $sA = $getShift($nikA);
                $sB = $getShift($nikB);
                if (!$sA && !$sB) {
                    $sA = 'S1';
                    $sB = 'S2';
                }
                $setShift($nikA, $sB ?: 'S2');
                $setShift($nikB, $sA ?: 'S1');

                $r['status'] = 'APPROVED';
                $r['approved_at'] = date('c');
                save_shift_requests($data_file, $rows);
                json_ok(['message' => 'Tukar shift disetujui dan diterapkan.']);
            } elseif ($action === 'reject' && ($r['status'] ?? '') === 'PENDING') {
                $r['status'] = 'REJECTED';
                $r['rejected_at'] = date('c');
                save_shift_requests($data_file, $rows);
                json_ok(['message' => 'Pengajuan ditolak.']);
            } elseif ($action === 'delete') {
                $r['__delete__'] = true;
                $rows = array_values(array_filter($rows, fn($x) => empty($x['__delete__'])));
                save_shift_requests($data_file, $rows);
                json_ok(['message' => 'Pengajuan dihapus.']);
            }
            break;
        }
    }

    json_error('Pengajuan tidak ditemukan.', 404);
}

$rows = load_shift_requests($data_file);
$niks = [];
foreach ($rows as $r) {
    if (!empty($r['requester'])) {
        $niks[$r['requester']] = true;
    }
    if (!empty($r['partner'])) {
        $niks[$r['partner']] = true;
    }
}
$nik_list = array_keys($niks);
$name_map = [];
if (!empty($nik_list)) {
    try {
        $placeholders = implode(',', array_fill(0, count($nik_list), '?'));
        $q = $conn->prepare("SELECT KODESL, NAMASL FROM T_SALES WHERE KODESL IN ($placeholders)");
        $q->execute($nik_list);
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $name_map[$row['KODESL']] = trim($row['NAMASL']);
        }
    } catch (PDOException $e) {
    }
}

json_ok([
    'data' => [
        'rows' => array_map(function ($r) use ($name_map) {
            return [
                'id' => $r['id'] ?? '',
                'date' => $r['date'] ?? '',
                'requester' => $r['requester'] ?? '',
                'requesterName' => $name_map[$r['requester']] ?? ($r['requester'] ?? ''),
                'partner' => $r['partner'] ?? '',
                'partnerName' => $name_map[$r['partner']] ?? ($r['partner'] ?? ''),
                'status' => $r['status'] ?? '',
                'created_at' => $r['created_at'] ?? null,
            ];
        }, $rows),
    ],
]);
