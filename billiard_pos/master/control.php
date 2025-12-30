<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login('admin');

$success = $error = '';

// Simpan perubahan IP/channel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_table'])) {
    $id = (int)($_POST['id'] ?? 0);
    $ip = trim($_POST['controller_ip'] ?? '');
    $ch = (int)($_POST['relay_channel'] ?? 0);
    if (!$id || $ip === '' || $ch <= 0) {
        $error = 'ID, IP, dan channel wajib diisi.';
    } else {
        $stmt = $pdo->prepare("UPDATE billiard_tables SET controller_ip = ?, relay_channel = ? WHERE id = ?");
        $stmt->execute([$ip, $ch, $id]);
        $success = 'Tersimpan.';
    }
}

// Kontrol on/off
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['table_id'])) {
    $tid = (int)$_POST['table_id'];
    $act = $_POST['action'];
    $row = $pdo->prepare("SELECT controller_ip, relay_channel FROM billiard_tables WHERE id = ?");
    $row->execute([$tid]);
    $tbl = $row->fetch();
    if ($tbl && $tbl['controller_ip'] && $tbl['relay_channel']) {
        try {
            $resp = call_lamp($tbl['controller_ip'], $tbl['relay_channel'], $act === 'on' ? 'on' : 'off');
            $success = "Lampu meja {$tid} {$act}. Resp: " . substr($resp,0,100);
        } catch (Exception $e) {
            $error = 'Gagal kontrol: ' . $e->getMessage();
        }
    } else {
        $error = 'IP/channel belum di-set.';
    }
}

// Tes koneksi ESP (hanya ping /status sederhana)
$testResults = [];
if (isset($_GET['test'])) {
    $tid = (int)$_GET['test'];
    $row = $pdo->prepare("SELECT controller_ip FROM billiard_tables WHERE id = ?");
    $row->execute([$tid]);
    if ($tbl = $row->fetch()) {
        $ip = $tbl['controller_ip'];
        if ($ip) {
            $url = "http://{$ip}/status";
            $resp = @file_get_contents($url);
            if ($resp !== false) {
                $testResults[$tid] = "OK: " . substr($resp,0,80);
            } else {
                $testResults[$tid] = "Gagal menghubungi {$url}";
            }
        } else {
            $testResults[$tid] = "IP kosong";
        }
    }
}

// Nyalakan/matikan semua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_all'])) {
    $act = $_POST['action_all'];
    $tablesAll = $pdo->query("SELECT id, controller_ip, relay_channel FROM billiard_tables WHERE controller_ip IS NOT NULL AND relay_channel IS NOT NULL")->fetchAll();
    $countOk = 0;
    foreach ($tablesAll as $t) {
        try {
            call_lamp($t['controller_ip'], $t['relay_channel'], $act === 'on_all' ? 'on' : 'off');
            $countOk++;
        } catch (Exception $e) {
            // abaikan error per meja
        }
    }
    $msg = ($act === 'on_all') ? "Semua lampu dinyalakan ($countOk meja)." : "Semua lampu dimatikan ($countOk meja).";
    $success = $msg;
    // notif WA ke target default jika ada
    $settings = load_company_settings();
    if (!empty($settings['fonnte_target']) && !empty($settings['fonnte_token'])) {
        send_fonnte_notification($settings['fonnte_target'], $msg);
    }
}

$tables = $pdo->query("SELECT * FROM billiard_tables ORDER BY id ASC")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Kontrol ESP</h4>
    <a href="/billiard_pos/tables/list.php" class="btn btn-outline-light btn-sm">Kembali</a>
 </div>
<div class="alert alert-info py-2">
    Tips: Anda bisa set IP/Channel manual di sini dan tes ON/OFF. Atau biarkan ESP update sendiri dengan memanggil
    <code>/billiard_pos/api/register_esp.php?table_id=ID&ip=IP&channel=CH</code> setelah konek WiFi.
    Pastikan IP & channel benar agar tombol ON/OFF berfungsi.
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" class="mb-3 d-flex gap-2">
    <button class="btn btn-success btn-sm" name="action_all" value="on_all">Nyalakan Semua Lampu</button>
    <button class="btn btn-danger btn-sm" name="action_all" value="off_all">Matikan Semua Lampu</button>
</form>
<div class="row">
    <?php foreach ($tables as $t): ?>
        <div class="col-md-6 mb-3">
            <div class="card bg-secondary text-light">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($t['name']); ?> (ID <?php echo $t['id']; ?>)</span>
                    <form method="post" class="d-flex gap-1 mb-0">
                        <input type="hidden" name="table_id" value="<?php echo $t['id']; ?>">
                        <button class="btn btn-sm btn-success" name="action" value="on">ON</button>
                        <button class="btn btn-sm btn-danger" name="action" value="off">OFF</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="small mb-2">
                        IP: <span class="badge bg-dark"><?php echo $t['controller_ip'] ?: 'kosong'; ?></span>
                        Channel: <span class="badge bg-dark"><?php echo $t['relay_channel'] ?: '-'; ?></span>
                        Status: <span class="badge bg-info text-dark">Aktif jika IP valid</span>
                    </div>
                    <div class="mb-2">
                        <a class="btn btn-sm btn-outline-light" target="_blank" href="/billiard_pos/api/register_esp.php?table_id=<?php echo $t['id']; ?>&ip=<?php echo urlencode($t['controller_ip']); ?>&channel=<?php echo urlencode($t['relay_channel']); ?>">Coba Register (API)</a>
                        <small class="text-muted d-block">Panggil endpoint ini dari ESP setelah konek WiFi.</small>
                    </div>
                    <form method="post" class="row g-2">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <div class="col-md-6">
                            <label class="form-label">IP</label>
                            <input type="text" name="controller_ip" class="form-control" value="<?php echo htmlspecialchars($t['controller_ip']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Channel</label>
                            <input type="number" name="relay_channel" class="form-control" value="<?php echo htmlspecialchars($t['relay_channel']); ?>">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary btn-sm" name="save_table">Simpan IP/Channel</button>
                        </div>
                    </form>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-secondary" href="?test=<?php echo $t['id']; ?>">Tes Koneksi</a>
                        <?php if (isset($testResults[$t['id']])): ?>
                            <small class="text-info"><?php echo htmlspecialchars($testResults[$t['id']]); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mt-2">
                        Jika ESP memanggil /api/register_esp.php?table_id=<?php echo $t['id']; ?>&ip=IP&channel=CH maka akan otomatis terisi.
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
