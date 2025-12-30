<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $price = (int)($_POST['special_price'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $duration <= 0 || $price <= 0) {
        $error = 'Nama, durasi, dan harga wajib diisi.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE packages SET name=?, duration_minutes=?, special_price=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $duration, $price, $is_active, $id]);
            $success = 'Paket diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (name, duration_minutes, special_price, is_active) VALUES (?,?,?,?)");
            $stmt->execute([$name, $duration, $price, $is_active]);
            $success = 'Paket ditambahkan.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM packages WHERE id = ?")->execute([$id]);
    header('Location: packages.php');
    exit;
}

$packages = $pdo->query("SELECT * FROM packages ORDER BY id DESC")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Paket Promo (2-3 jam, dsb)</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-md-5">
        <div class="card bg-secondary text-light">
            <div class="card-header"><?php echo $editData ? 'Edit Paket' : 'Tambah Paket'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama Paket</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durasi (menit)</label>
                        <input type="number" name="duration_minutes" class="form-control" required value="<?php echo htmlspecialchars($editData['duration_minutes'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Spesial</label>
                        <input type="number" name="special_price" class="form-control" required value="<?php echo htmlspecialchars($editData['special_price'] ?? ''); ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active" id="is_active" <?php echo (($editData['is_active'] ?? 1) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <?php if ($editData): ?><a href="packages.php" class="btn btn-outline-light">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card bg-secondary text-light">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Paket</span>
                <span class="badge bg-dark">Aktif: <?php echo count(array_filter($packages, fn($p)=>$p['is_active'])); ?></span>
            </div>
            <div class="card-body">
                <table class="table table-dark table-striped align-middle">
                    <thead><tr><th>#</th><th>Nama</th><th>Durasi</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($packages as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo $p['duration_minutes']; ?> mnt</td>
                            <td><?php echo format_rupiah($p['special_price']); ?></td>
                            <td><?php echo $p['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus paket?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
