<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

function column_exists(PDO $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :t AND COLUMN_NAME = :c");
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
}

function pick_column(PDO $conn, string $table, array $candidates, ?string $fallback = null): ?string {
    foreach ($candidates as $col) {
        if (column_exists($conn, $table, $col)) {
            return $col;
        }
    }
    return $fallback;
}

$bulan = $_GET['bulan'] ?? date('Y-m');
$start = $bulan . '-01';
$end = date('Y-m-t', strtotime($start));

$selected_employees = get_selected_employees();
$selected_niks = array_values(array_filter(array_map(function ($row) {
    return $row['nik'] ?? '';
}, $selected_employees)));

$col_nik = pick_column($conn, 'T_SALES', ['KODESL', 'NIK'], 'KODESL');
$col_nama = pick_column($conn, 'T_SALES', ['NAMASL', 'NAMA'], 'NAMASL');
$col_jabatan = pick_column($conn, 'T_SALES', ['JABATAN', 'JABATANSL', 'POSISI'], null);
$select_jabatan = $col_jabatan ? "$col_jabatan AS JABATAN" : "'' AS JABATAN";

if (!empty($selected_niks)) {
    $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
    $stmt = $conn->prepare("SELECT $col_nik AS NIK, $col_nama AS NAMA, $select_jabatan FROM T_SALES WHERE $col_nik IN ($placeholders) ORDER BY $col_nama");
    $stmt->execute($selected_niks);
} else {
    $stmt = $conn->query("SELECT $col_nik AS NIK, $col_nama AS NAMA, $select_jabatan FROM T_SALES ORDER BY $col_nama");
}
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$target_per_orang = 0;
$jumlah_pegawai = count($selected_niks);
if (file_exists(__DIR__ . '/target_manual.json')) {
    $json = json_decode(file_get_contents(__DIR__ . '/target_manual.json'), true);
    if (is_array($json)) {
        $target_data = $json[$bulan] ?? $json;
        if (!empty($target_data['per_orang']) && $jumlah_pegawai > 0) {
            $target_per_orang = (float)$target_data['per_orang'];
        }
    }
}

$bonus_map = [
    5 => 30000,
    10 => 40000,
    15 => 50000,
    20 => 60000,
];
$bonus_default = $bonus_map[$jumlah_pegawai] ?? 0;

$data_gaji = [];
$payroll = load_payroll_json($bulan);
$payroll_items = $payroll['items'] ?? [];
$payroll_map = [];
if (!empty($payroll_items)) {
    foreach ($payroll_items as $item) {
        if (empty($item['nik'])) {
            continue;
        }
        $payroll_map[$item['nik']] = $item;
    }
}

foreach ($sales as $s) {
    $nik = $s['NIK'];
    $nama = $s['NAMA'];
    $jabatan = $s['JABATAN'];

    $item = $payroll_map[$nik] ?? null;
    if ($item) {
        $bonus_total = (int)($item['bonus'] ?? 0) + (int)($item['bonus_absensi'] ?? 0);
        $penjualan = (float)($item['penjualan'] ?? 0);
        $persen = $target_per_orang > 0 ? (int)round($penjualan / $target_per_orang * 100) : 0;

        $data_gaji[] = [
            'nik' => $nik,
            'nama' => $item['nama'] ?? $nama,
            'jabatan' => $item['jabatan'] ?? $jabatan,
            'gajiPokok' => (int)($item['gapok'] ?? 0),
            'komisi' => (int)($item['komisi'] ?? 0),
            'lembur' => (int)($item['lembur'] ?? 0),
            'bonus' => $bonus_total,
            'absensi' => (int)($item['absen_disetujui_days'] ?? 0),
            'bpjs' => (int)($item['tunj_bpjs'] ?? 0),
            'tunjanganJabatan' => (int)($item['tunj_jabatan'] ?? 0),
            'penaltyAbsensi' => (int)($item['potongan'] ?? 0),
            'total' => (int)($item['total'] ?? 0),
            'persenTarget' => $persen,
        ];
        continue;
    }

    $stmt_jual = $conn->prepare("SELECT SUM(NETTO) as total FROM V_JUAL WHERE KODESL = :nik AND TGL BETWEEN :start AND :end");
    $stmt_jual->execute(['nik' => $nik, 'start' => $start, 'end' => $end]);
    $total_jual = (float)($stmt_jual->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $komisi = (int)round($total_jual * 0.01);

    $persen = $target_per_orang > 0 ? (int)round($total_jual / $target_per_orang * 100) : 0;
    $bonus = $persen >= 100 ? $bonus_default : 0;

    $gaji_pokok = 850000;
    if ($nik === '001') {
        $gaji_pokok = 875000;
    }

    $total = $gaji_pokok + $komisi + $bonus;

    $data_gaji[] = [
        'nik' => $nik,
        'nama' => $nama,
        'jabatan' => $jabatan,
        'gajiPokok' => $gaji_pokok,
        'komisi' => $komisi,
        'lembur' => 0,
        'bonus' => $bonus,
        'absensi' => 0,
        'bpjs' => 0,
        'tunjanganJabatan' => 0,
        'penaltyAbsensi' => 0,
        'total' => $total,
        'persenTarget' => $persen,
    ];
}

json_ok([
    'data' => [
        'bulan' => $bulan,
        'targetPerOrang' => $target_per_orang,
        'rows' => $data_gaji,
    ],
]);
