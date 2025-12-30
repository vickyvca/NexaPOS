<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$target = preg_replace('/\D+/', '', (string)($payload['target'] ?? ''));
if ($target === '') {
    json_error('Nomor WhatsApp wajib diisi.', 400);
}

$message = sprintf(
    "Pesan percobaan:\nSilakan abaikan.\nTerima kasih.\n%s",
    date('d F Y / H:i')
);

try {
    $result = kirimWATeksFonnte($target, $message, 'Supplier WA Test');
    if ($result === false) {
        json_error('Gagal mengirim pesan test via WhatsApp.', 500);
    }
    json_ok(['message' => 'Pesan test berhasil dikirim.']);
} catch (Throwable $e) {
    json_error('Gagal mengirim pesan: ' . $e->getMessage(), 500);
}
