<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$accounts = $pdo->query("SELECT * FROM accounts WHERE is_active = 1 ORDER BY name")->fetchAll();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = (int)($_POST['account_id'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);
    $expense_time = $_POST['expense_time'] ?: date('Y-m-d\TH:i');

    if (!$account_id || !$category || $amount <= 0) {
        $error = 'Akun, kategori, dan nominal wajib diisi.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO expenses (account_id, category, description, amount, expense_time, operator_id) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$account_id, $category, $description, $amount, str_replace('T', ' ', $expense_time), $_SESSION['user']['id']]);
        $expense_id = $pdo->lastInsertId();
        add_journal($pdo, $account_id, 'out', $amount, 'Pengeluaran: ' . $category, 'expense', $expense_id);
        $success = 'Pengeluaran tercatat.';
    }
}

$history = $pdo->query("SELECT e.*, a.name AS account_name, u.username FROM expenses e JOIN accounts a ON e.account_id = a.id LEFT JOIN users u ON e.operator_id = u.id ORDER BY e.expense_time DESC LIMIT 50")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pengeluaran Operasional</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Dashboard</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>

<div class="card bg-secondary text-light mb-3">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Akun</label>
                <select name="account_id" class="form-select" required>
                    <option value="">Pilih akun</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?> (<?php echo $acc['type']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <input type="text" name="category" class="form-control" placeholder="Listrik, sewa, dll" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal & Jam</label>
                <input type="datetime-local" name="expense_time" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Nominal</label>
                <input type="number" name="amount" class="form-control" min="1000" step="500" required>
            </div>
            <div class="col-12">
                <label class="form-label">Keterangan</label>
                <input type="text" name="description" class="form-control" placeholder="Opsional">
            </div>
            <div class="col-12">
                <button class="btn btn-success">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>
</div>

<div class="card bg-secondary text-light">
    <div class="card-header">Riwayat Terakhir</div>
    <div class="card-body">
        <table class="table table-dark table-striped">
            <thead><tr><th>Waktu</th><th>Akun</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th><th>Operator</th></tr></thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo format_datetime($h['expense_time']); ?></td>
                        <td><?php echo htmlspecialchars($h['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($h['category']); ?></td>
                        <td><?php echo htmlspecialchars($h['description']); ?></td>
                        <td><?php echo format_rupiah($h['amount']); ?></td>
                        <td><?php echo htmlspecialchars($h['username']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$history): ?>
                    <tr><td colspan="6" class="text-center text-muted">Belum ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
