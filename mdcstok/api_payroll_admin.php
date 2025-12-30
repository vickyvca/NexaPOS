<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

$bulan = $_GET['bulan'] ?? ym_now();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $bulan = $input['bulan'] ?? $bulan;
    $updates = $input['updates'] ?? [];

    $payroll = load_payroll_json($bulan);
    if (!is_array($payroll)) {
        json_error('Data payroll tidak valid.', 500);
    }

    foreach ($payroll['items'] as &$it) {
        $nik = $it['nik'] ?? '';
        if (!$nik || !isset($updates[$nik]) || !is_array($updates[$nik])) {
            continue;
        }
        $u = $updates[$nik];
        $it['jabatan'] = trim((string)($u['jabatan'] ?? $it['jabatan'] ?? ''));
        $it['gapok'] = (float)($u['gapok'] ?? $it['gapok'] ?? 0);
        $it['tunj_jabatan'] = (float)($u['tunj_jabatan'] ?? $it['tunj_jabatan'] ?? 0);
        $it['tunj_bpjs'] = (float)($u['tunj_bpjs'] ?? $it['tunj_bpjs'] ?? 0);
        $it['potongan'] = (float)($u['potongan'] ?? $it['potongan'] ?? 0);
        $it['catatan'] = trim((string)($u['catatan'] ?? $it['catatan'] ?? ''));

        if (array_key_exists('absen_disetujui_days', $u) || array_key_exists('bonus_absensi', $u) || array_key_exists('absen_ot_days', $u) || array_key_exists('lembur', $u)) {
            $it['absen_disetujui_days'] = (int)($u['absen_disetujui_days'] ?? $it['absen_disetujui_days'] ?? 0);
            $it['bonus_absensi'] = (float)($u['bonus_absensi'] ?? $it['bonus_absensi'] ?? 0);
            $it['absen_ot_days'] = (int)($u['absen_ot_days'] ?? $it['absen_ot_days'] ?? 0);
            $it['lembur'] = (float)($u['lembur'] ?? $it['lembur'] ?? 0);
            $it['manual_attendance'] = 1;
        }
    }
    unset($it);

    $sales_map = get_sales_total_by_nik($conn, $bulan);
    recalc_items($payroll, $sales_map, $bulan);
    save_payroll_json($bulan, $payroll);

    json_ok(['message' => 'Payroll berhasil disimpan.']);
}

$payroll = load_payroll_json($bulan);
if (!isset($conn)) {
    json_error('Koneksi DB tidak tersedia.', 500);
}

$prev_month = date('Y-m', strtotime($bulan . ' -1 month'));
$payroll_prev = load_payroll_json($prev_month);
$prev_items_map = [];
if (!empty($payroll_prev['items']) && is_array($payroll_prev['items'])) {
    $prev_items_map = array_column($payroll_prev['items'], null, 'nik');
}

$selected = get_selected_employees();
$selected_niks = array_values(array_filter(array_map(function ($row) {
    return $row['nik'] ?? '';
}, $selected)));

$names_map = [];
if (!empty($selected_niks)) {
    try {
        $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
        $stmtNames = $conn->prepare("SELECT KODESL, NAMASL FROM T_SALES WHERE KODESL IN ($placeholders)");
        $stmtNames->execute($selected_niks);
        while ($row = $stmtNames->fetch(PDO::FETCH_ASSOC)) {
            $names_map[$row['KODESL']] = trim($row['NAMASL']);
        }
    } catch (PDOException $e) {
    }
}

foreach ($selected_niks as $nik) {
    $nama = $names_map[$nik] ?? $nik;
    ensure_item($payroll, $nik, $nama);
    foreach ($payroll['items'] as &$it) {
        if (($it['nik'] ?? '') === $nik) {
            $it['nama'] = $nama;
            if (isset($prev_items_map[$nik])) {
                $prev_item = $prev_items_map[$nik];
                foreach (['jabatan', 'gapok', 'tunj_jabatan', 'tunj_bpjs'] as $field) {
                    if (empty($it[$field])) {
                        $it[$field] = $prev_item[$field] ?? (is_numeric($prev_item[$field] ?? null) ? 0 : '');
                    }
                }
            }
        }
    }
    unset($it);
}

$sales_map = get_sales_total_by_nik($conn, $bulan);
recalc_items($payroll, $sales_map, $bulan);

$items = [];
$sum = [
    'gapok' => 0,
    'komisi' => 0,
    'bonus_individu' => 0,
    'bonus_kolektif' => 0,
    'bonus_absensi' => 0,
    'lembur' => 0,
    'tunj_jabatan' => 0,
    'tunj_bpjs' => 0,
    'potongan' => 0,
    'total' => 0,
];

foreach ($payroll['items'] as $it) {
    if (!empty($selected_niks) && !in_array($it['nik'], $selected_niks, true)) {
        continue;
    }
    $items[] = $it;
    $sum['gapok'] += (float)($it['gapok'] ?? 0);
    $sum['komisi'] += (float)($it['komisi'] ?? 0);
    $sum['bonus_individu'] += (float)($it['bonus_individu'] ?? 0);
    $sum['bonus_kolektif'] += (float)($it['bonus_kolektif'] ?? 0);
    $sum['bonus_absensi'] += (float)($it['bonus_absensi'] ?? 0);
    $sum['lembur'] += (float)($it['lembur'] ?? 0);
    $sum['tunj_jabatan'] += (float)($it['tunj_jabatan'] ?? 0);
    $sum['tunj_bpjs'] += (float)($it['tunj_bpjs'] ?? 0);
    $sum['potongan'] += (float)($it['potongan'] ?? 0);
    $sum['total'] += (float)($it['total'] ?? 0);
}

json_ok([
    'data' => [
        'bulan' => $bulan,
        'items' => $items,
        'summary' => $sum,
    ],
]);
