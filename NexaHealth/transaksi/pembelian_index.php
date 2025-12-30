<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
$pdo = getPDO();
$rows = $pdo->query("SELECT p.*, s.name supplier, u.name creator FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id LEFT JOIN users u ON u.id=p.created_by ORDER BY p.id DESC LIMIT 100")->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h4>Pembelian</h4>
    <a class="btn btn-sm btn-primary" href="pembelian_form.php">Tambah</a>
</div>
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Supplier</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Oleh</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($r['purchase_no']); ?></td>
        <td><?= htmlspecialchars($r['supplier']); ?></td>
        <td><?= htmlspecialchars($r['date']); ?></td>
        <td class="text-end"><?= format_rupiah($r['total']); ?></td>
        <td><span class="badge bg-<?= $r['status']=='posted'?'success':'secondary'; ?>"><?= htmlspecialchars($r['status']); ?></span></td>
        <td><?= htmlspecialchars($r['creator']); ?></td>
        <td><a class="btn btn-sm btn-secondary" href="pembelian_form.php?id=<?= $r['id']; ?>">Detail</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
