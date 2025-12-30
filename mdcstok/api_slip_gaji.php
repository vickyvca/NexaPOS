<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

$bulan = $_GET['bulan'] ?? 'this';
$target_ym = $bulan === 'this' ? date('Y-m') : $bulan;
if (!preg_match('/^\d{4}-\d{2}$/', $target_ym)) {
    $target_ym = date('Y-m');
}

$available = [];
$cursor = strtotime(date('Y-m-01'));
for ($i = 0; $i < 12; $i++) {
    $ym = date('Y-m', strtotime("-$i month", $cursor));
    $file = payroll_file_for($ym);
    if (!is_file($file)) {
        continue;
    }
    $js = json_decode(@file_get_contents($file), true);
    if (!is_array($js) || empty($js['items'])) {
        continue;
    }
    foreach ($js['items'] as $row) {
        if (($row['nik'] ?? '') === $nik) {
            $available[] = [
                'value' => $ym,
                'label' => date('F Y', strtotime($ym . '-01')),
            ];
            break;
        }
    }
}

$payroll = load_payroll_json($target_ym);
$slip = null;
if (!empty($payroll['items'])) {
    foreach ($payroll['items'] as $row) {
        if (($row['nik'] ?? '') === $nik) {
            $slip = $row;
            break;
        }
    }
}

json_ok([
    'data' => [
        'bulan' => $target_ym,
        'brand' => $PAYROLL_BRAND_NAME ?? 'MODESTOK HR',
        'available' => $available,
        'slip' => $slip,
    ],
]);
