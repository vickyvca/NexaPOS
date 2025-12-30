<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

if (empty($_SESSION['nama_pegawai'])) {
    try {
        $stmt_nama = $conn->prepare("SELECT NAMASL FROM T_SALES WHERE KODESL = :nik");
        $stmt_nama->execute([':nik' => $nik]);
        $result = $stmt_nama->fetch(PDO::FETCH_ASSOC);
        $_SESSION['nama_pegawai'] = ($result && !empty($result['NAMASL'])) ? trim($result['NAMASL']) : 'Pegawai';
    } catch (PDOException $e) {
        $_SESSION['nama_pegawai'] = 'Pegawai';
    }
}

$my_wa = get_pegawai_wa($nik);
$ym_now = date('Y-m');
$metrics_ym = $_GET['bulan'] ?? $ym_now;
$month_start = $metrics_ym . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$ot_pribadi = 0;
$ot_partner = 0;
$libur_hari = 0;
$jadwal_libur = [];

try {
    $sql_ot = "SELECT 
                  SUM(CASE WHEN OVERTIME_BONUS_FLAG=1 AND (OVERTIME_NOTES LIKE '%OT_PARTNER%') THEN 1 ELSE 0 END) AS ot_partner,
                  SUM(CASE WHEN OVERTIME_BONUS_FLAG=1 AND (OVERTIME_NOTES NOT LIKE '%OT_PARTNER%' OR OVERTIME_NOTES IS NULL) THEN 1 ELSE 0 END) AS ot_pribadi
               FROM T_ABSENSI
               WHERE KODESL = :nik AND TGL BETWEEN :d1 AND :d2";
    $st_ot = $conn->prepare($sql_ot);
    $st_ot->execute([':nik' => $nik, ':d1' => $month_start, ':d2' => $month_end]);
    $row_ot = $st_ot->fetch(PDO::FETCH_ASSOC) ?: [];
    $ot_pribadi = (int)($row_ot['ot_pribadi'] ?? 0);
    $ot_partner = (int)($row_ot['ot_partner'] ?? 0);

    $sql_lv = "SELECT TGL_MULAI, TGL_SELESAI, JENIS_CUTI FROM T_PENGAJUAN_LIBUR
               WHERE KODESL = :nik AND STATUS = 'APPROVED' AND TGL_MULAI <= :end AND TGL_SELESAI >= :start";
    $st_lv = $conn->prepare($sql_lv);
    $st_lv->execute([':nik' => $nik, ':start' => $month_start, ':end' => $month_end]);
    while ($lv = $st_lv->fetch(PDO::FETCH_ASSOC)) {
        $start = new DateTime(max($month_start, $lv['TGL_MULAI']));
        $end = new DateTime(min($month_end, $lv['TGL_SELESAI']));
        $days = $start->diff($end)->days + 1;
        $libur_hari += $days;
        $jadwal_libur[] = [
            'mulai' => $start->format('Y-m-d'),
            'selesai' => $end->format('Y-m-d'),
            'jenis' => $lv['JENIS_CUTI'] ?? '-',
        ];
    }
} catch (PDOException $e) {
}

$bonus_individu_now = 0;
$bonus_kolektif_now = 0;
$komisi_now = 0;
try {
    $payroll_now = load_payroll_json($metrics_ym);
    if (!empty($payroll_now['items'])) {
        foreach ($payroll_now['items'] as $it) {
            if (($it['nik'] ?? '') === $nik) {
                $bonus_individu_now = (int)($it['bonus_individu'] ?? 0);
                $bonus_kolektif_now = (int)($it['bonus_kolektif'] ?? 0);
                $komisi_now = (int)($it['komisi'] ?? 0);
                break;
            }
        }
    }
} catch (Exception $e) {
}

$items_perhatian = [];
try {
    $days_in_period = (int)date('t', strtotime($month_start));
    $q = $conn->prepare("SELECT TOP 5 KODEBRG, NAMABRG, SUM(QTY) AS total_qty 
                         FROM V_JUAL 
                         WHERE KODESL = :nik AND TGL BETWEEN :d1 AND :d2 
                         GROUP BY KODEBRG, NAMABRG 
                         ORDER BY SUM(QTY) DESC");
    $q->execute([':nik' => $nik, ':d1' => $month_start, ':d2' => $month_end]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $kode = (string)$row['KODEBRG'];
        $total = (float)($row['total_qty'] ?? 0);
        $avg = $days_in_period > 0 ? ($total / $days_in_period) : 0;
        $qs = $conn->prepare("SELECT SUM(ST00+ST01+ST02+ST03+ST04) FROM T_BARANG WHERE KODEBRG = :kb");
        $qs->execute([':kb' => $kode]);
        $stok = (int)($qs->fetchColumn() ?? 0);
        $avg_eff = max($avg, 0.01);
        $coverage = round($stok / $avg_eff, 1);
        $risk = ($coverage <= 7) ? 'Ya' : 'Tidak';
        $items_perhatian[] = [
            'kode' => $kode,
            'nama' => (string)$row['NAMABRG'],
            'stok' => $stok,
            'avg' => round($avg, 2),
            'coverage' => $coverage,
            'risk' => $risk,
        ];
    }
} catch (PDOException $e) {
}

$bulan = date('Y-m');
$target_per_orang = 0;
$semua_target = file_exists(__DIR__ . '/target_manual.json') ? json_decode(file_get_contents(__DIR__ . '/target_manual.json'), true) : [];
$target_bulan_data = is_array($semua_target) ? ($semua_target[$bulan] ?? $semua_target) : null;
$target_per_orang = (float)($target_bulan_data['per_orang'] ?? 0);

$realisasi = 0;
try {
    $stmt = $conn->prepare("SELECT SUM(NETTO) as total FROM V_JUAL WHERE KODESL = :nik AND TGL BETWEEN :start AND :end");
    $stmt->execute(['nik' => $nik, 'start' => date('Y-m-01'), 'end' => date('Y-m-t')]);
    $realisasi = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (PDOException $e) {
}

$komisi = (int)round($realisasi * 0.01);
$user_rank = 0;
$total_salespeople = 0;
try {
    $stmt_rank = $conn->prepare(
        "SELECT KODESL, SUM(NETTO) as total_penjualan
         FROM V_JUAL WHERE TGL BETWEEN :start AND :end
         GROUP BY KODESL ORDER BY total_penjualan DESC"
    );
    $stmt_rank->execute(['start' => date('Y-m-01'), 'end' => date('Y-m-t')]);
    $all_sales_data = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);
    $total_salespeople = count($all_sales_data);
    $rank = 1;
    foreach ($all_sales_data as $sales_row) {
        if (($sales_row['KODESL'] ?? '') === $nik) {
            $user_rank = $rank;
            break;
        }
        $rank++;
    }
} catch (PDOException $e) {
}

$today_date = date('Y-m-d');
$nota_hari_ini = 0;
try {
    $stmt_nota = $conn->prepare("SELECT COUNT(DISTINCT NONOTA) AS total_nota FROM V_JUAL WHERE KODESL = :nik AND CONVERT(date, TGL) = :tgl");
    $stmt_nota->execute([':nik' => $nik, ':tgl' => $today_date]);
    $nota_hari_ini = (int)($stmt_nota->fetchColumn() ?? 0);
} catch (PDOException $e) {
}

$shift_times = [
    'S1' => ['start' => '08:30', 'end' => '18:00', 'name' => 'Shift 1 (Pagi)'],
    'S2' => ['start' => '12:00', 'end' => '20:30', 'name' => 'Shift 2 (Siang)'],
];
$teams = file_exists(__DIR__ . '/employee_teams.json') ? json_decode(file_get_contents(__DIR__ . '/employee_teams.json'), true) : [];
$is_team_A = in_array($nik, $teams['team_A'] ?? []);
$is_team_B = in_array($nik, $teams['team_B'] ?? []);
$start_date = new DateTime('2025-01-01');
$today_dt = new DateTime($today_date);
$days_diff = $start_date->diff($today_dt)->days;
$is_odd_day = ($days_diff % 2 !== 0);
if ($is_team_A) {
    $pegawai_shift = $is_odd_day ? 'S2' : 'S1';
} elseif ($is_team_B) {
    $pegawai_shift = $is_odd_day ? 'S1' : 'S2';
} else {
    $pegawai_shift = 'N/A';
}
$my_shift_label = $pegawai_shift === 'N/A' ? 'Tidak Terjadwal' : $shift_times[$pegawai_shift]['name'];
$my_shift_start_time = $pegawai_shift === 'N/A' ? '-' : $shift_times[$pegawai_shift]['start'];
$my_shift_end_time = $pegawai_shift === 'N/A' ? '-' : $shift_times[$pegawai_shift]['end'];

json_ok([
    'data' => [
        'pegawai' => [
            'nik' => $nik,
            'nama' => $_SESSION['nama_pegawai'] ?? 'Pegawai',
            'targetPerOrang' => $target_per_orang,
            'realisasi' => $realisasi,
            'komisi' => $komisi,
            'peringkat' => $user_rank ?: 0,
            'totalSalespeople' => $total_salespeople ?: 0,
            'shiftHariIni' => $pegawai_shift,
            'shiftLabel' => $my_shift_label,
            'shiftStartTime' => $my_shift_start_time,
            'shiftEndTime' => $my_shift_end_time,
            'otPribadi' => $ot_pribadi,
            'otPartner' => $ot_partner,
            'liburHari' => $libur_hari,
            'bonusIndividu' => $bonus_individu_now,
            'bonusKolektif' => $bonus_kolektif_now,
            'komisiNow' => $komisi_now,
            'notaHariIni' => $nota_hari_ini,
            'myWa' => $my_wa ?: '',
        ],
        'itemsPerhatian' => $items_perhatian,
        'jadwalLibur' => $jadwal_libur,
    ],
]);
