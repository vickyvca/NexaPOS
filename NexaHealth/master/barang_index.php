<?php
require_once __DIR__ . '/../middleware.php';
check_csrf();
$pdo = getPDO();
$categories = fetch_options('categories');

if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM items WHERE id=?");
    $stmt->execute([$_GET['del']]);
    redirect('/master/barang_index.php');
}

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? '';
$active = $_GET['active'] ?? '';
$offset = ($page-1)*$perPage;
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(code LIKE ? OR barcode LIKE ? OR name LIKE ?)";
    $params[] = "%{$search}%"; $params[]="%{$search}%"; $params[]="%{$search}%";
}
if ($cat !== '') { $where[] = "category_id = ?"; $params[] = $cat; }
if ($active !== '') { $where[] = "is_active = ?"; $params[] = $active; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT i.*, c.name AS cat_name FROM items i LEFT JOIN categories c ON c.id=i.category_id $whereSql ORDER BY i.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Barang <a class="btn btn-sm btn-primary" href="barang_form.php"><i class="bi bi-plus"></i> Tambah</a></h4>
<form class="row g-2 mb-3">
    <div class="col-md-3"><input class="form-control" name="q" placeholder="Cari kode/barcode/nama" value="<?= htmlspecialchars($search); ?>"></div>
    <div class="col-md-3">
        <select class="form-select" name="cat">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id']; ?>" <?= $cat==$c['id']?'selected':''; ?>><?= htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="active">
            <option value="">Aktif & Tidak</option>
            <option value="1" <?= $active==='1'?'selected':''; ?>>Aktif</option>
            <option value="0" <?= $active==='0'?'selected':''; ?>>Nonaktif</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-secondary">Filter</button></div>
</form>
<table class="table table-bordered table-sm">
    <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga L1/L2/L3</th><th>Stok</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['code']); ?><br><small><?= htmlspecialchars($r['barcode']); ?></small></td>
        <td><?= htmlspecialchars($r['name']); ?><br><span class="badge text-bg-<?= $r['is_active']?'success':'secondary'; ?>"><?= $r['is_active']?'Aktif':'Nonaktif'; ?></span></td>
        <td><?= htmlspecialchars($r['cat_name']); ?></td>
        <td>
            L1: <?= format_rupiah($r['sell_price']); ?><br>
            L2: <?= format_rupiah($r['sell_price_lv2']); ?><br>
            L3: <?= format_rupiah($r['sell_price_lv3']); ?>
        </td>
        <td><?= $r['stock']; ?> / Min <?= $r['min_stock']; ?></td>
        <td>
            <a class="btn btn-sm btn-secondary" href="barang_form.php?id=<?= $r['id']; ?>">Edit</a>
            <a class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')" href="?del=<?= $r['id']; ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php $totalPages = ceil($total/$perPage); if ($totalPages>1): ?>
<nav><ul class="pagination">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
        <li class="page-item <?= $i==$page?'active':''; ?>"><a class="page-link" href="?page=<?= $i; ?>&q=<?= urlencode($search); ?>&cat=<?= $cat; ?>&active=<?= $active; ?>"><?= $i; ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php'; ?>
