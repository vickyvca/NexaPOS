<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Supplier tidak teridentifikasi.', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $incoming = $payload['waNumbers'] ?? [];
    $sanitized = [];
    for ($i = 0; $i < 5; $i++) {
        $raw = trim((string)($incoming[$i] ?? ''));
        $clean = preg_replace('/\D+/', '', $raw);
        $sanitized[] = $clean;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE T_SUPLIER SET
                WA_1 = :wa1,
                WA_2 = :wa2,
                WA_3 = :wa3,
                WA_4 = :wa4,
                WA_5 = :wa5
            WHERE KODESP = :kodesp
        ");
        $stmt->execute([
            ':wa1' => $sanitized[0] ?: null,
            ':wa2' => $sanitized[1] ?: null,
            ':wa3' => $sanitized[2] ?: null,
            ':wa4' => $sanitized[3] ?: null,
            ':wa5' => $sanitized[4] ?: null,
            ':kodesp' => $kodesp,
        ]);
        json_ok(['data' => ['waNumbers' => array_values(array_filter($sanitized))]]);
    } catch (PDOException $e) {
        json_error('Gagal menyimpan nomor WhatsApp: ' . $e->getMessage(), 500);
    }
}

try {
    $stmt = $conn->prepare("SELECT WA_1, WA_2, WA_3, WA_4, WA_5 FROM T_SUPLIER WHERE KODESP = :kodesp");
    $stmt->execute([':kodesp' => $kodesp]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $waNumbers = [];
    for ($i = 1; $i <= 5; $i++) {
        $value = trim((string)($row["WA_$i"] ?? ''));
        if ($value !== '') {
            $waNumbers[] = $value;
        }
    }
    json_ok(['data' => ['waNumbers' => $waNumbers]]);
} catch (PDOException $e) {
    json_error('Gagal memuat nomor WhatsApp: ' . $e->getMessage(), 500);
}
