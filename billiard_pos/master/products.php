<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $category === '') {
        $error = 'Nama dan kategori wajib diisi.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $category, $price, $stock, $is_active, $id]);
            $success = 'Produk diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, is_active) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $category, $price, $stock, $is_active]);
            $success = 'Produk ditambahkan.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header('Location: products.php');
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Produk</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row">
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header"><?php echo $editData ? 'Edit Produk' : 'Tambah Produk'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select" required>
                            <?php $categories = ['minuman','snack','makanan','lain-lain']; ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo (($editData['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" name="price" class="form-control" required value="<?php echo htmlspecialchars($editData['price'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($editData['stock'] ?? 0); ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo (($editData['is_active'] ?? 1) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <?php if ($editData): ?><a href="products.php" class="btn btn-outline-light">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Produk</span>
                <span class="badge bg-dark">Aktif: <?php echo count(array_filter($products, fn($p)=>$p['is_active'])); ?></span>
            </div>
            <div class="card-body">
                <table class="table table-dark table-striped">
                    <thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($p['category']); ?></span></td>
                            <td><?php echo format_rupiah($p['price']); ?></td>
                            <td><?php echo $p['stock']; ?></td>
                            <td><?php echo $p['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>'; ?></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk?')">Hapus</a>
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
