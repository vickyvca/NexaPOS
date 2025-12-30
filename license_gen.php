<?php
require_once __DIR__ . '/helpers.php';

$keyOut = '';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = strtoupper(trim($_POST['type'] ?? 'TRIAL'));
    $exp = trim($_POST['exp'] ?? 'NA');
    if ($type === 'TRIAL' && $exp === 'NA') {
        $msg = 'Untuk TRIAL harus isi tanggal kadaluarsa (yyyymmdd)';
    } else {
        $payload = 'NEXA-' . $type . '-' . $exp;
        $sign = substr(hash_hmac('sha256', $payload, LICENSE_SECRET),0,12);
        $keyOut = $payload . '-' . $sign;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>License Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container" style="max-width:600px; margin-top:40px;">
    <div class="card bg-secondary text-white">
        <div class="card-body">
            <h4 class="mb-3">Generate License Key</h4>
            <?php if ($msg): ?><div class="alert alert-warning"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
            <form method="post" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" name="type">
                        <option value="FULL">FULL</option>
                        <option value="TRIAL">TRIAL</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exp (TRIAL yyyymmdd)</label>
                    <input class="form-control" name="exp" placeholder="20251231 atau NA" value="<?= htmlspecialchars($_POST['exp'] ?? 'NA'); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Generate</button>
                </div>
            </form>
            <?php if ($keyOut): ?>
                <hr>
                <div class="alert alert-success">
                    <div class="fw-semibold">License Key:</div>
                    <div style="word-break:break-all; font-family:monospace;">
                        <?= htmlspecialchars($keyOut); ?>
                    </div>
                </div>
            <?php endif; ?>
            <a class="btn btn-outline-light" href="<?= BASE_URL; ?>/license.php">Aktivasi</a>
        </div>
    </div>
</div>
</body>
</html>
