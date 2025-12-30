<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir','owner']);
$pdo = getPDO();
$rows = $pdo->query("SELECT s.*, u.name kasir FROM sales s LEFT JOIN users u ON u.id=s.created_by ORDER BY s.id DESC LIMIT 200")->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h4>Riwayat Penjualan</h4>
</div>
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Tanggal</th><th>Kasir</th><th>Pelanggan</th><th>Total</th><th>Grand</th><th>Bayar</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($r['sale_no']); ?></td>
        <td><?= $r['date']; ?></td>
        <td><?= htmlspecialchars($r['kasir']); ?></td>
        <td><?= htmlspecialchars($r['customer_name']); ?></td>
        <td class="text-end"><?= format_rupiah($r['total']); ?></td>
        <td class="text-end"><?= format_rupiah($r['grand_total']); ?></td>
        <td class="text-end"><?= format_rupiah($r['cash_paid']); ?></td>
        <td>
            <a class="btn btn-sm btn-outline-light" href="print.php?id=<?= $r['id']; ?>" target="_blank">Cetak</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
