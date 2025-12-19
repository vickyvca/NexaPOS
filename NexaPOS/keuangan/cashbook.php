<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','owner']);
$pdo = getPDO();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $amount = (float)preg_replace('/[^0-9]/','', $_POST['amount']);
    $stmt = $pdo->prepare("INSERT INTO cashbooks(date,type,amount,note,ref_type,created_by) VALUES(?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['date'],
        $_POST['type'],
        $amount,
        trim($_POST['note']),
        $_POST['ref_type'],
        current_user()['id']
    ]);
    redirect('/keuangan/cashbook.php?from='.$from.'&to='.$to);
}

if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cashbook.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal','Jenis','Kategori','Nominal','Catatan']);
    $rows = $pdo->prepare("SELECT * FROM cashbooks WHERE date BETWEEN ? AND ? ORDER BY date DESC, id DESC");
    $rows->execute([$from,$to]);
    foreach ($rows as $r) {
        fputcsv($out, [$r['date'], $r['type'], $r['ref_type'], $r['amount'], $r['note']]);
    }
    exit;
}

$rows = $pdo->prepare("SELECT c.*, u.name creator FROM cashbooks c LEFT JOIN users u ON u.id=c.created_by WHERE c.date BETWEEN ? AND ? ORDER BY c.date DESC, c.id DESC");
$rows->execute([$from,$to]);
$data = $rows->fetchAll();

$in = $pdo->prepare("SELECT SUM(amount) FROM cashbooks WHERE type='in' AND date BETWEEN ? AND ?");
$in->execute([$from,$to]);
$totalIn = $in->fetchColumn() ?: 0;
$out = $pdo->prepare("SELECT SUM(amount) FROM cashbooks WHERE type='out' AND date BETWEEN ? AND ?");
$out->execute([$from,$to]);
$totalOut = $out->fetchColumn() ?: 0;
$saldo = $totalIn - $totalOut;
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Buku Kas</h4>
    <div class="no-print">
        <a class="btn btn-outline-light btn-sm" href="?from=<?= $from; ?>&to=<?= $to; ?>&export=csv"><i class="bi bi-download"></i> Export CSV</a>
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
</form>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6 class="mb-3">Tambah Pemasukan/Pengeluaran</h6>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                <div class="mb-2">
                    <label>Tanggal</label>
                    <input type="date" class="form-control" name="date" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-2">
                    <label>Jenis</label>
                    <select class="form-select" name="type" required>
                        <option value="in">Pemasukan</option>
                        <option value="out">Pengeluaran</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Kategori</label>
                    <select class="form-select" name="ref_type">
                        <option value="lain">Lain-lain</option>
                        <option value="income">Pemasukan Lain</option>
                        <option value="operasional">Operasional</option>
                        <option value="salary">Gaji</option>
                        <option value="expense">Biaya</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Nominal</label>
                    <input type="text" class="form-control rupiah-input" name="amount" required>
                </div>
                <div class="mb-2">
                    <label>Catatan</label>
                    <input class="form-control" name="note" placeholder="Keterangan singkat">
                </div>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="report-area mb-2">
            <div class="d-flex justify-content-between">
                <div>Pemasukan: <strong><?= format_rupiah($totalIn); ?></strong></div>
                <div>Pengeluaran: <strong><?= format_rupiah($totalOut); ?></strong></div>
                <div>Saldo: <strong><?= format_rupiah($saldo); ?></strong></div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-sm">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Nominal</th><th>Catatan</th><th>User</th></tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($data as $r): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($r['date']); ?></td>
                        <td><span class="badge bg-<?= $r['type']=='in'?'success':'danger'; ?>"><?= $r['type']; ?></span></td>
                        <td><?= htmlspecialchars($r['ref_type']); ?></td>
                        <td><?= format_rupiah($r['amount']); ?></td>
                        <td><?= htmlspecialchars($r['note']); ?></td>
                        <td><?= htmlspecialchars($r['creator']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$data): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
