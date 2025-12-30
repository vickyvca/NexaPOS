<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

$selected_file = __DIR__ . '/target_selected.json';
$manual_file = __DIR__ . '/target_manual.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }
    $bulan = $input['bulan'] ?? '';
    $total_raw = $input['total'] ?? '0';
    $total_target = (int)str_replace([',', '.', ' '], '', (string)$total_raw);

    $selected_niks = file_exists($selected_file) ? json_decode(file_get_contents($selected_file), true) : [];
    $jumlah_nik = is_array($selected_niks) ? count($selected_niks) : 0;

    if ($jumlah_nik === 0 || !$bulan || $total_target <= 0) {
        json_error('Mohon lengkapi semua data dan pastikan ada NIK terpilih.', 400);
    }

    $target_per_orang = (int)round($total_target / $jumlah_nik);
    $existing = file_exists($manual_file) ? json_decode(file_get_contents($manual_file), true) : [];
    if (!is_array($existing)) {
        $existing = [];
    }
    $history = [];
    foreach ($existing as $key => $value) {
        if (preg_match('/^\d{4}-\d{2}$/', (string)$key) && is_array($value)) {
            $history[$key] = $value;
        }
    }
    if (empty($history) && !empty($existing['bulan'])) {
        $history[$existing['bulan']] = $existing;
    }

    $history[$bulan] = [
        'bulan' => $bulan,
        'total' => $total_target,
        'per_orang' => $target_per_orang,
        'updated_at' => date('c'),
    ];
    file_put_contents($manual_file, json_encode($history, JSON_PRETTY_PRINT));

    json_ok([
        'data' => [
            'bulan' => $bulan,
            'total' => $total_target,
            'per_orang' => $target_per_orang,
            'jumlah_nik' => $jumlah_nik,
        ],
    ]);
}

$selected_list = file_exists($selected_file) ? json_decode(file_get_contents($selected_file), true) : [];
$selected_niks = [];
foreach ($selected_list as $nik_nama) {
    $parts = explode(' - ', (string)$nik_nama, 2);
    $nik = trim($parts[0] ?? '');
    if ($nik) {
        $selected_niks[] = $nik;
    }
}

$pegawai = [];
if (!empty($selected_niks)) {
    $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
    $stmt = $conn->prepare("SELECT KODESL, NAMASL FROM T_SALES WHERE KODESL IN ($placeholders)");
    $stmt->execute($selected_niks);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pegawai[] = ['nik' => $row['KODESL'], 'nama' => trim($row['NAMASL'])];
    }
}

$target_data = file_exists($manual_file) ? json_decode(file_get_contents($manual_file), true) : [];
$history_map = [];
if (is_array($target_data)) {
    foreach ($target_data as $key => $value) {
        if (preg_match('/^\d{4}-\d{2}$/', (string)$key) && is_array($value)) {
            $entry = $value;
            if (empty($entry['bulan'])) {
                $entry['bulan'] = $key;
            }
            $history_map[$key] = $entry;
        }
    }
    if (empty($history_map) && !empty($target_data['bulan'])) {
        $history_map[$target_data['bulan']] = $target_data;
    }
}
$history_list = array_values($history_map);
usort($history_list, function ($a, $b) {
    return strcmp((string)($b['bulan'] ?? ''), (string)($a['bulan'] ?? ''));
});
if (!empty($history_map) && isset($history_map[date('Y-m')])) {
    $target_data = $history_map[date('Y-m')];
} elseif (!empty($history_list)) {
    $target_data = $history_list[0];
}

$history_perf = [];
if (!empty($history_map)) {
    $selected_employees = get_selected_employees();
    $selected_niks = array_values(array_filter(array_map(function ($row) {
        return $row['nik'] ?? '';
    }, $selected_employees)));
    $name_map = [];
    if (!empty($selected_niks)) {
        $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
        try {
            $stmtNames = $conn->prepare("SELECT KODESL, NAMASL FROM T_SALES WHERE KODESL IN ($placeholders)");
            $stmtNames->execute($selected_niks);
            while ($row = $stmtNames->fetch(PDO::FETCH_ASSOC)) {
                $name_map[$row['KODESL']] = trim($row['NAMASL']);
            }
        } catch (PDOException $e) {
        }
    }

    foreach ($history_list as $hist) {
        $bulan = $hist['bulan'] ?? '';
        if (!$bulan) {
            continue;
        }
        $start = $bulan . '-01';
        $end = date('Y-m-t', strtotime($start));
        $realisasi_map = [];
        $total_kolektif = 0;
        if (!empty($selected_niks)) {
            $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
            $sql = "SELECT KODESL, SUM(NETTO) AS total FROM V_JUAL WHERE KODESL IN ($placeholders) AND TGL BETWEEN ? AND ? GROUP BY KODESL";
            $params = array_merge($selected_niks, [$start, $end]);
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $realisasi_map[$row['KODESL']] = (float)($row['total'] ?? 0);
                    $total_kolektif += (float)($row['total'] ?? 0);
                }
            } catch (PDOException $e) {
            }
        }

        $pegawai_rows = [];
        foreach ($selected_niks as $nik) {
            $pegawai_rows[] = [
                'nik' => $nik,
                'nama' => $name_map[$nik] ?? $nik,
                'realisasi' => $realisasi_map[$nik] ?? 0,
            ];
        }

        $history_perf[] = [
            'bulan' => $bulan,
            'total_kolektif' => $total_kolektif,
            'pegawai' => $pegawai_rows,
        ];
    }
}
json_ok([
    'data' => [
        'selected' => $pegawai,
        'target' => $target_data,
        'history' => $history_list,
        'history_performance' => $history_perf,
    ],
]);
