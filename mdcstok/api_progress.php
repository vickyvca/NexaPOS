<?php
require_once __DIR__ . '/api_common.php';
require_roles(['admin']);

$bulan_param = $_GET['bulan'] ?? date('Y-m');
$tanggal_awal = $bulan_param . '-01';
$tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));

$target_per_orang = 0;
$target_total = 0;
if (file_exists(__DIR__ . '/target_manual.json')) {
    $tj = json_decode(file_get_contents(__DIR__ . '/target_manual.json'), true);
    if (is_array($tj)) {
        $target_data = $tj[$bulan_param] ?? $tj;
        $target_total = (float)($target_data['total'] ?? 0);
        $target_per_orang = (float)($target_data['per_orang'] ?? 0);
    }
}

$selected_niks = [];
$sales_names = [];
$selected = file_exists(__DIR__ . '/target_selected.json') ? json_decode(file_get_contents(__DIR__ . '/target_selected.json'), true) : [];
if (is_array($selected) && !empty($selected)) {
    foreach ($selected as $nik_nama) {
        $parts = explode(' - ', (string)$nik_nama, 2);
        $nik = trim($parts[0] ?? '');
        if ($nik) {
            $selected_niks[] = $nik;
        }
    }
    if (!empty($selected_niks)) {
        $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
        try {
            $stmtNames = $conn->prepare("SELECT KODESL, NAMASL FROM T_SALES WHERE KODESL IN ($placeholders)");
            $stmtNames->execute($selected_niks);
            while ($row = $stmtNames->fetch(PDO::FETCH_ASSOC)) {
                $sales_names[$row['KODESL']] = trim($row['NAMASL']);
            }
        } catch (PDOException $e) {
        }
    }
}

$jumlah_spg = count($selected_niks);
$realisasi_pegawai = [];
$total_kolektif = 0.0;
if ($jumlah_spg > 0) {
    $placeholders = implode(',', array_fill(0, count($selected_niks), '?'));
    try {
        $sql = "SELECT KODESL, SUM(NETTO) as total 
                FROM V_JUAL 
                WHERE KODESL IN ($placeholders) AND TGL BETWEEN ? AND ?
                GROUP BY KODESL";
        $params = array_merge($selected_niks, [$tanggal_awal, $tanggal_akhir]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $realisasi_pegawai[$row['KODESL']] = (float)$row['total'];
            $total_kolektif += (float)$row['total'];
        }
    } catch (PDOException $e) {
    }
}

$target_levels = [
    5 => $target_total,
    10 => round($target_total * 1.10),
    15 => round($target_total * 1.15),
    20 => round($target_total * 1.20),
];
$bonus_persen_map = [5 => 0.005, 10 => 0.010, 15 => 0.015, 20 => 0.020];

$level_kolektif = 0;
if ($target_total > 0) {
    foreach ([20, 15, 10, 5] as $lvl) {
        if ($total_kolektif >= $target_levels[$lvl]) {
            $level_kolektif = $lvl;
            break;
        }
    }
}
$capaian_kolektif = $target_total > 0 ? (int)round(($total_kolektif / $target_total) * 100) : 0;

function calculate_final_values(array $pegawai, int $level_kolektif, array $bonus_map, float $target_orang): array {
    $realisasi = (float)$pegawai['realisasi'];
    $level_individu = (int)$pegawai['level_individu'];
    $level_final = max($level_kolektif, $level_individu);

    $komisi = round($realisasi * 0.01);
    $percent_final = $level_final > 0 ? (float)($bonus_map[$level_final] ?? 0) : 0.0;
    $percent_individu = $level_individu > 0 ? (float)($bonus_map[$level_individu] ?? 0) : 0.0;

    $bonus_individu = round($realisasi * $percent_individu);
    $bonus_kolektif_base = 0;
    if ($percent_final > $percent_individu) {
        $bonus_kolektif_base = round($realisasi * ($percent_final - $percent_individu));
    }
    $bonus_kolektif_extra = 0;
    if ($level_kolektif >= 5 && $level_individu > 0) {
        $bonus_kolektif_extra = round($realisasi * 0.005);
    }

    $bonus_kolektif = $bonus_kolektif_base + $bonus_kolektif_extra;
    $bonus = $bonus_individu + $bonus_kolektif;
    $total = $komisi + $bonus;
    $persen = $target_orang > 0 ? round(($realisasi / $target_orang) * 100) : 0;

    return [
        'komisi' => (int)$komisi,
        'bonus' => (int)$bonus,
        'bonus_individu' => (int)$bonus_individu,
        'bonus_kolektif' => (int)$bonus_kolektif,
        'total' => (int)$total,
        'persen' => (int)$persen,
        'level' => $level_final,
    ];
}

$data_pegawai = [];
foreach ($selected_niks as $nik) {
    $realisasi = $realisasi_pegawai[$nik] ?? 0;
    $level_individu = 0;
    if ($jumlah_spg > 0) {
        foreach ([20, 15, 10, 5] as $lvl) {
            $target_level_total = $target_levels[$lvl];
            if ($target_level_total > 0) {
                $ind_target = $target_level_total / $jumlah_spg;
                if ($realisasi >= $ind_target) {
                    $level_individu = $lvl;
                    break;
                }
            }
        }
    }
    $pegawai = [
        'nik' => $nik,
        'nama' => $sales_names[$nik] ?? $nik,
        'realisasi' => $realisasi,
        'level_individu' => $level_individu,
    ];
    $calculated = calculate_final_values($pegawai, $level_kolektif, $bonus_persen_map, $target_per_orang);
    $data_pegawai[] = array_merge($pegawai, $calculated);
}

json_ok([
    'data' => [
        'bulan' => $bulan_param,
        'targetTotal' => $target_total,
        'targetPerOrang' => $target_per_orang,
        'totalKolektif' => $total_kolektif,
        'levelKolektif' => $level_kolektif,
        'capaianKolektif' => $capaian_kolektif,
        'pegawai' => $data_pegawai,
    ],
]);
