<?php
require_once __DIR__ . '/../middleware.php';
check_csrf();
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $id = $_POST['id'] ?? '';
    if ($name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
            $stmt->execute([$name, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories(name) VALUES(?)");
            $stmt->execute([$name]);
        }
    }
    redirect('/master/kategori_index.php');
}
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
    $stmt->execute([$_GET['del']]);
    redirect('/master/kategori_index.php');
}
$data = fetch_options('categories');
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Kategori</h4>
<form class="row g-2 mb-3" method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? ''); ?>">
    <div class="col-md-5">
        <input class="form-control" name="name" placeholder="Nama kategori" value="<?php
            if (!empty($_GET['id'])) {
                $id = (int)$_GET['id'];
                foreach ($data as $c) if ($c['id']==$id) echo htmlspecialchars($c['name']);
            }
        ?>" required>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary">Simpan</button>
    </div>
</form>
<table class="table table-bordered">
    <tr><th>#</th><th>Nama</th><th>Action</th></tr>
    <?php foreach ($data as $c): ?>
    <tr>
        <td><?= $c['id']; ?></td>
        <td><?= htmlspecialchars($c['name']); ?></td>
        <td>
            <a class="btn btn-sm btn-secondary" href="?id=<?= $c['id']; ?>">Edit</a>
            <a class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')" href="?del=<?= $c['id']; ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
