<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','owner']);
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $amount = (float)preg_replace('/[^0-9]/','', $_POST['amount']);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO salaries(employee,period,amount,date_paid,note,created_by) VALUES(?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['employee'],
            $_POST['period'],
            $amount,
            $_POST['date_paid'],
            $_POST['note'],
            current_user()['id']
        ]);
        $sid = $pdo->lastInsertId();
        // Catat ke cashbook untuk arus kas
        $pdo->prepare("INSERT INTO cashbooks(date,type,amount,note,ref_type,ref_id,created_by) VALUES(?,?,?,?,?,?,?)")
            ->execute([$_POST['date_paid'],'out',$amount,'Gaji '.$_POST['employee'], 'salary', $sid, current_user()['id']]);
        $pdo->commit();
    } catch(Exception $e) {
        $pdo->rollBack();
        log_error($e->getMessage());
    }
    redirect('/keuangan/salary.php');
}

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->prepare("DELETE FROM salaries WHERE id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM cashbooks WHERE ref_type='salary' AND ref_id=?")->execute([$id]);
    redirect('/keuangan/salary.php');
}

$rows = $pdo->query("SELECT * FROM salaries ORDER BY date_paid DESC, id DESC")->fetchAll();
$total = $pdo->query("SELECT SUM(amount) FROM salaries")->fetchColumn() ?: 0;
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Gaji / Payroll</h4>
    <div class="no-print">
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card p-3">
            <h6 class="mb-3">Input Gaji</h6>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                <div class="mb-2">
                    <label>Nama Karyawan</label>
                    <input class="form-control" name="employee" required>
                </div>
                <div class="mb-2">
                    <label>Periode</label>
                    <input class="form-control" name="period" placeholder="Desember 2025" required>
                </div>
                <div class="mb-2">
                    <label>Nominal</label>
                    <input type="text" class="form-control rupiah-input" name="amount" required>
                </div>
                <div class="mb-2">
                    <label>Tanggal Bayar</label>
                    <input type="date" class="form-control" name="date_paid" value="<?= date('Y-m-d'); ?>" required>
                </div>
                <div class="mb-2">
                    <label>Catatan</label>
                    <input class="form-control" name="note">
                </div>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="report-area mb-2">Total Gaji Dibayar: <strong><?= format_rupiah($total); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-sm">
                <thead><tr><th>#</th><th>Karyawan</th><th>Periode</th><th>Tgl Bayar</th><th>Nominal</th><th>Catatan</th><th class="no-print">Aksi</th></tr></thead>
                <tbody>
                    <?php $i=1; foreach ($rows as $r): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($r['employee']); ?></td>
                        <td><?= htmlspecialchars($r['period']); ?></td>
                        <td><?= htmlspecialchars($r['date_paid']); ?></td>
                        <td><?= format_rupiah($r['amount']); ?></td>
                        <td><?= htmlspecialchars($r['note']); ?></td>
                        <td class="no-print">
                            <a class="btn btn-sm btn-danger" href="?del=<?= $r['id']; ?>" onclick="return confirm('Hapus data?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
