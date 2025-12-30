<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

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
    $date = $input['date'] ?? '';
    $partner = $input['partner'] ?? '';
    if (!$date || !$partner) {
        json_error('Tanggal dan partner wajib diisi.', 400);
    }
    if ($partner === $nik) {
        json_error('Partner tidak boleh diri sendiri.', 400);
    }
    $rows = load_shift_requests($data_file);
    $rows[] = [
        'id' => uniqid('SR'),
        'requester' => $nik,
        'partner' => $partner,
        'date' => $date,
        'status' => 'PENDING',
        'created_at' => date('c'),
    ];
    save_shift_requests($data_file, $rows);

    $admins = get_admin_wa_list();
    if (!empty($admins)) {
        $nama = $nik;
        $namap = $partner;
        try {
            $q = $conn->prepare("SELECT KODESL,NAMASL FROM T_SALES WHERE KODESL IN (?,?)");
            $q->execute([$nik, $partner]);
            while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
                if ($r['KODESL'] === $nik) {
                    $nama = $r['NAMASL'];
                }
                if ($r['KODESL'] === $partner) {
                    $namap = $r['NAMASL'];
                }
            }
        } catch (Exception $e) {
        }
        $pesan = wa_tpl_shift_swap_submitted_admin($date, $nama, $nik, $namap, $partner);
        foreach ($admins as $wa) {
            kirimWATeksFonnte($wa, $pesan);
        }
    }

    json_ok(['message' => 'Pengajuan tukar shift dikirim. Menunggu persetujuan admin.']);
}

$rows = load_shift_requests($data_file);
$my_rows = array_values(array_filter($rows, function ($r) use ($nik) {
    return ($r['requester'] ?? '') === $nik || ($r['partner'] ?? '') === $nik;
}));

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
        'requests' => $my_rows,
        'partners' => $partners,
    ],
]);
