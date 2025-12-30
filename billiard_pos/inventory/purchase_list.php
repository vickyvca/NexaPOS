<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$purchases = $pdo->query("SELECT p.*, u.username FROM purchases p LEFT JOIN users u ON p.operator_id = u.id ORDER BY p.purchase_time DESC")->fetchAll();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Riwayat Pembelian</h4>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/inventory/purchase_create.php" class="btn btn-success btn-sm">Tambah Pembelian</a>
        <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
    </div>
</div>
<div class="card bg-secondary text-light">
    <div class="card-body">
        <table class="table table-dark table-striped align-middle">
            <thead><tr><th>Tanggal</th><th>Supplier</th><th>Total</th><th>Operator</th><th>Catatan</th></tr></thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
                <tr>
                    <td><?php echo format_datetime($p['purchase_time']); ?></td>
                    <td><?php echo htmlspecialchars($p['supplier']); ?></td>
                    <td><?php echo format_rupiah($p['total']); ?></td>
                    <td><?php echo htmlspecialchars($p['username']); ?></td>
                    <td><?php echo htmlspecialchars($p['note']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$purchases): ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada pembelian</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
