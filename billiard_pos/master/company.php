<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';
$file = __DIR__ . '/../company.json';
$company = load_company_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company['name'] = trim($_POST['name'] ?? '');
    $company['tagline'] = trim($_POST['tagline'] ?? '');
    $company['phone'] = trim($_POST['phone'] ?? '');
    $company['address'] = trim($_POST['address'] ?? '');
    $company['fonnte_token'] = trim($_POST['fonnte_token'] ?? '');
    $company['fonnte_target'] = trim($_POST['fonnte_target'] ?? '');
    $company['maintenance_password'] = trim($_POST['maintenance_password'] ?? '');

    if (!empty($_FILES['logo']['name'])) {
        $targetDir = __DIR__ . '/../assets/img';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $targetFile = $targetDir . '/logo.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
            $company['logo'] = 'assets/img/' . basename($targetFile);
        } else {
            $error = 'Gagal upload logo.';
        }
    }
    if (!$error) {
        file_put_contents($file, json_encode($company, JSON_PRETTY_PRINT));
        $success = 'Informasi perusahaan disimpan.';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Profil Perusahaan</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="card bg-secondary text-light">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Perusahaan</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($company['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="tagline" class="form-control" value="<?php echo htmlspecialchars($company['tagline']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telepon/HP</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($company['phone']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($company['address']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fonnte Token</label>
                    <input type="text" name="fonnte_token" class="form-control" value="<?php echo htmlspecialchars($company['fonnte_token'] ?? ''); ?>" placeholder="Isi token untuk WA notif">
                    <small class="text-muted">Digunakan untuk notifikasi start meja.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No WA Penerima Notifikasi</label>
                    <input type="text" name="fonnte_target" class="form-control" value="<?php echo htmlspecialchars($company['fonnte_target'] ?? ''); ?>" placeholder="08xxxx (opsional)">
                    <small class="text-muted">Jika diisi, notifikasi juga dikirim ke nomor ini.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password Kontrol Lampu</label>
                    <input type="text" name="maintenance_password" class="form-control" value="<?php echo htmlspecialchars($company['maintenance_password'] ?? ''); ?>" placeholder="Isi untuk wajib password lampu">
                    <small class="text-muted">Kosongkan jika tidak ingin proteksi lampu.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo (PNG/JPG)</label>
                    <input type="file" name="logo" class="form-control">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <?php if (!empty($company['logo']) && file_exists(__DIR__ . '/../' . $company['logo'])): ?>
                        <img src="/billiard_pos/<?php echo $company['logo']; ?>" alt="Logo" style="height:60px;" class="me-3">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
