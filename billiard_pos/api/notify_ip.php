<?php
// Kirim notif WA via Fonnte.
// Panggil:
//   - notify_ip.php?ip=192.168.x.x&mode=STA   (pesan otomatis IP)
//   - notify_ip.php?msg=Halo                  (pesan custom)
//   - tambahkan ?target=62xxxx jika mau override penerima.

$token  = 'k88yMFvkbhZ8eXkMsYym';         // token Fonnte
$target = $_GET['target'] ?? '6285290970944'; // nomor default
$ip     = $_GET['ip']     ?? '';
$mode   = $_GET['mode']   ?? '';
$msg    = $_GET['msg']    ?? '';

if ($msg === '' && $ip !== '') {
    $msg = "ESP terkoneksi.\nIP: $ip\nMode: $mode";
} elseif ($msg === '') {
    $msg = "Test pesan WA dari modecentre.cloud.";
}

$ch = curl_init("https://api.fonnte.com/send");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: $token"],
    CURLOPT_POSTFIELDS     => [
        'target'  => $target,
        'message' => $msg
    ],
    CURLOPT_TIMEOUT        => 10,
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false || $code >= 400) {
    http_response_code(500);
    echo "failed: " . ($err ?: $res);
    exit;
}
echo "ok";
