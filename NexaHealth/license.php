<?php
require_once __DIR__ . '/helpers.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $key = trim($_POST['license_key'] ?? '');
    $res = validate_license($key);
    if ($res['ok']) {
        file_put_contents(LICENSE_FILE, $key);
        $msg = 'Lisensi valid: ' . $res['type'] . ($res['type']==='TRIAL' ? ' exp '.$res['exp'] : '');
    } else {
        $msg = 'Gagal: '.$res['msg'];
    }
}
$current = get_license_key();
$resCur = validate_license($current);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Aktivasi Lisensi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container" style="max-width:600px; margin-top:60px;">
    <div class="card bg-secondary text-white">
        <div class="card-body">
            <h4 class="mb-3">Aktivasi Lisensi</h4>
            <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
            <p>Status saat ini: <strong><?= $resCur['ok'] ? 'VALID ('.$resCur['type'].')' : 'TIDAK VALID'; ?></strong></p>
            <form method="post">
                <div class="mb-3">
                    <label>Masukkan License Key</label>
                    <input class="form-control" name="license_key" value="<?= htmlspecialchars($current); ?>" required>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a class="btn btn-outline-light" href="<?= BASE_URL; ?>/auth/login.php">Login</a>
            </form>
            <hr>
            <small>Gunakan key dari admin. Format: NEXA-TYPE-EXP-SIGN. Trial: EXP adalah yyyymmdd.</small>
        </div>
    </div>
</div>
</body>
</html>
