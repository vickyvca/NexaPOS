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

try {
    $selected = get_selected_employees();
    $selected_niks = array_values(array_filter(array_map(function ($row) {
        return $row['nik'] ?? '';
    }, $selected)));

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
    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            'nik' => $row['NIK'],
            'nama' => $row['NAMA'],
            'jabatan' => $row['JABATAN'] ?? '',
        ];
    }
    json_ok(['data' => ['rows' => $rows]]);
} catch (PDOException $e) {
    json_error('Gagal memuat data pegawai: ' . $e->getMessage(), 500);
}
