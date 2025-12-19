<?php
require_once __DIR__ . '/middleware.php';
ensure_role(['admin']);
$pdo = getPDO();
$tables = ['users','categories','suppliers','items','purchases','purchase_items','sales','sale_items','stock_moves','returns','return_items'];

if (isset($_GET['export'])) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=nexapos-backup-' . date('Ymd-His') . '.sql');
    echo "-- NexaPOS backup " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $t) {
        echo "TRUNCATE TABLE `$t`;\n";
        $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) continue;
        foreach ($rows as $row) {
            $cols = array_map(fn($c)=>"`$c`", array_keys($row));
            $vals = array_map(fn($v)=> $v===null ? 'NULL' : $pdo->quote($v), array_values($row));
            echo "INSERT INTO `$t` (" . implode(',',$cols) . ") VALUES(" . implode(',',$vals) . ");\n";
        }
        echo "\n";
    }
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!empty($_FILES['sql']['tmp_name'])) {
        $sql = file_get_contents($_FILES['sql']['tmp_name']);
        try {
            $pdo->beginTransaction();
            foreach (explode(';', $sql) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt) $pdo->exec($stmt);
            }
            $pdo->commit();
            $message = 'Restore berhasil';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Restore gagal: ' . $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/layout/header.php'; ?>
<h4><i class="bi bi-hdd-network"></i> Backup / Restore</h4>
<?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message); ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="h6 mb-2">Export</div>
                <p class="small text-muted">Download backup SQL (data saja). Gunakan admin DB untuk import jika diperlukan.</p>
                <a class="btn btn-primary" href="?export=1"><i class="bi bi-download"></i> Download SQL</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="h6 mb-2">Restore</div>
                <p class="small text-warning">Pastikan memiliki backup. Operasi ini akan menjalankan SQL upload apa adanya.</p>
                <form method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                    <input type="file" class="form-control" name="sql" accept=".sql" required>
                    <button class="btn btn-danger"><i class="bi bi-upload"></i> Restore</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
