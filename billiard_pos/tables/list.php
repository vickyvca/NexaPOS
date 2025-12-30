<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $ip = trim($_POST['controller_ip'] ?? '');
    $relay = (int)($_POST['relay_channel'] ?? 0);
    $status = $_POST['status'] ?? 'idle';
    $category = $_POST['category'] ?? 'regular';

    if ($name === '') {
        $error = 'Nama meja wajib diisi.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE billiard_tables SET name = ?, controller_ip = ?, relay_channel = ?, status = ?, category = ? WHERE id = ?");
            $stmt->execute([$name, $ip, $relay, $status, $category, $id]);
            $success = 'Data meja diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO billiard_tables (name, controller_ip, relay_channel, status, category) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $ip, $relay, $status, $category]);
            $success = 'Meja baru ditambahkan.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM billiard_tables WHERE id = ?")->execute([$id]);
    header('Location: list.php');
    exit;
}

$tables = $pdo->query("SELECT * FROM billiard_tables ORDER BY id ASC")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM billiard_tables WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Meja Billiard</h4>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/master/control.php" class="btn btn-warning btn-sm">Master Kontrol</a>
        <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
    </div>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row">
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header"><?php echo $editData ? 'Edit Meja' : 'Tambah Meja'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama Meja</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IP Controller</label>
                        <input type="text" name="controller_ip" class="form-control" value="<?php echo htmlspecialchars($editData['controller_ip'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relay Channel</label>
                        <input type="number" name="relay_channel" class="form-control" value="<?php echo htmlspecialchars($editData['relay_channel'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select">
                            <?php
                                $options = [
                                    'regular' => 'Regular',
                                    'vip' => 'VIP',
                                    'vvip' => 'VVIP',
                                    'student' => 'Pelajar'
                                ];
                                $cat = $editData['category'] ?? 'regular';
                                foreach ($options as $k => $label) {
                                    echo '<option value="'.$k.'"'.($cat === $k ? ' selected' : '').'>'.$label.'</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Awal</label>
                        <select name="status" class="form-select">
                            <?php foreach (['idle','running','paused'] as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo (($editData['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <?php if ($editData): ?>
                        <a href="list.php" class="btn btn-outline-light">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header">Daftar Meja</div>
            <div class="card-body">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>IP</th>
                            <th>Relay</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tables as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><?php echo htmlspecialchars($t['name']); ?></td>
                            <td><?php echo htmlspecialchars($t['controller_ip']); ?></td>
                            <td><?php echo htmlspecialchars($t['relay_channel']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($t['category'] ?? 'regular')); ?></td>
                            <td><?php echo $t['status']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $t['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus meja ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
