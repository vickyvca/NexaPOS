<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';

function generate_code() {
    return 'MBR' . str_pad((string)rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $discount = (int)($_POST['discount_percent'] ?? 0);
    $points = (int)($_POST['points'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $phone === '') {
        $error = 'Nama dan HP wajib diisi.';
    } else {
        if ($code === '') {
            $code = generate_code();
        }
        if ($id) {
            $stmt = $pdo->prepare("UPDATE members SET code=?, name=?, phone=?, discount_percent=?, points=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $name, $phone, $discount, $points, $is_active, $id]);
            $success = 'Member diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO members (code, name, phone, discount_percent, points, is_active) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$code, $name, $phone, $discount, $points, $is_active]);
            $success = 'Member ditambahkan.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Cek referensi di orders atau sessions
    $c1 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE member_id = ?");
    $c1->execute([$id]);
    $c2 = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE member_id = ?");
    $c2->execute([$id]);
    if ($c1->fetchColumn() > 0 || $c2->fetchColumn() > 0) {
        $error = 'Member tidak bisa dihapus karena sudah dipakai di transaksi. Nonaktifkan saja.';
    } else {
        $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
        header('Location: members.php');
        exit;
    }
}

$members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Member</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-md-5">
        <div class="card bg-secondary text-light">
            <div class="card-header"><?php echo $editData ? 'Edit Member' : 'Tambah Member'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Kode Member</label>
                        <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($editData['code'] ?? ''); ?>" placeholder="Auto jika kosong">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($editData['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diskon (%)</label>
                        <input type="number" name="discount_percent" class="form-control" value="<?php echo htmlspecialchars($editData['discount_percent'] ?? 0); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poin</label>
                        <input type="number" name="points" class="form-control" value="<?php echo htmlspecialchars($editData['points'] ?? 0); ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active" id="is_active" <?php echo (($editData['is_active'] ?? 1) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <?php if ($editData): ?><a href="members.php" class="btn btn-outline-light">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card bg-secondary text-light">
            <div class="card-header">Daftar Member</div>
            <div class="card-body">
                <table class="table table-dark table-striped align-middle">
                    <thead><tr><th>#</th><th>Kode</th><th>Nama</th><th>HP</th><th>Diskon</th><th>Poin</th><th>Aktif</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?php echo $m['id']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($m['code']); ?></span></td>
                            <td><?php echo htmlspecialchars($m['name']); ?></td>
                            <td><?php echo htmlspecialchars($m['phone']); ?></td>
                            <td><?php echo $m['discount_percent']; ?>%</td>
                            <td><?php echo $m['points']; ?></td>
                            <td><?php echo $m['is_active'] ? 'Ya' : 'Tidak'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $m['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus member?')">Hapus</a>
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
