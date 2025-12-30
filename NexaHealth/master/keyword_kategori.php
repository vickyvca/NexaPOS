<?php
require_once __DIR__ . '/../middleware.php';
$pdo = getPDO();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $cat = (int)($_POST['category_id'] ?? 0);
    $keyword = trim($_POST['keyword'] ?? '');
    if ($cat && $keyword) {
        $stmt = $pdo->prepare("INSERT INTO category_keywords(category_id, keyword) VALUES(?,?)");
        $stmt->execute([$cat,$keyword]);
        $msg = 'Keyword ditambah';
    }
}
if (isset($_GET['del'])) {
    check_csrf();
    $stmt = $pdo->prepare("DELETE FROM category_keywords WHERE id=?");
    $stmt->execute([$_GET['del']]);
    redirect('/master/keyword_kategori.php');
}
$cats = fetch_options('categories');
$rows = $pdo->query("SELECT ck.*, c.name cat_name FROM category_keywords ck JOIN categories c ON c.id=ck.category_id ORDER BY c.name, ck.keyword")->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Keyword Kategori</h4>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
<form class="row g-2 mb-3" method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <div class="col-md-4">
        <select class="form-select" name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <input class="form-control" name="keyword" placeholder="Keyword (contoh: rokok, gula, kopi)" required>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Tambah</button></div>
</form>
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>#</th><th>Kategori</th><th>Keyword</th><th></th></tr></thead>
    <tbody>
    <?php $no=1; foreach ($rows as $r): ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= htmlspecialchars($r['cat_name']); ?></td>
        <td><?= htmlspecialchars($r['keyword']); ?></td>
        <td><a class="btn btn-sm btn-danger" href="?del=<?= $r['id']; ?>&csrf=<?= csrf_token(); ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
