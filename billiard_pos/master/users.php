<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'kasir';
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $error = 'Username wajib diisi.';
    } else {
        if ($id) {
            if ($password !== '') {
                $stmt = $pdo->prepare("UPDATE users SET username=?, phone=?, role=?, password_hash=? WHERE id=?");
                $stmt->execute([$username, $phone, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, phone=?, role=? WHERE id=?");
                $stmt->execute([$username, $phone, $role, $id]);
            }
            $success = 'User diperbarui.';
        } else {
            if ($password === '') {
                $error = 'Password wajib diisi untuk user baru.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, phone, password_hash, role) VALUES (?,?,?,?)");
                $stmt->execute([$username, $phone, password_hash($password, PASSWORD_DEFAULT), $role]);
                $success = 'User ditambahkan.';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === ($_SESSION['user']['id'] ?? 0)) {
        $error = 'Tidak bisa menghapus diri sendiri.';
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        header('Location: users.php');
        exit;
    }
}

$users = $pdo->query("SELECT id, username, phone, role, created_at FROM users ORDER BY id DESC")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master User</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-md-5">
        <div class="card bg-secondary text-light">
            <div class="card-header"><?php echo $editData ? 'Edit User' : 'Tambah User'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editData['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($editData['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. WA / Phone (untuk notif racker)</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editData['phone'] ?? ''); ?>" placeholder="misal 628xxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <?php echo $editData ? '(kosongkan jika tidak ganti)' : ''; ?></label>
                        <input type="password" name="password" class="form-control" <?php echo $editData ? '' : 'required'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin" <?php echo (($editData['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="kasir" <?php echo (($editData['role'] ?? 'kasir') === 'kasir') ? 'selected' : ''; ?>>Kasir</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <?php if ($editData): ?><a href="users.php" class="btn btn-outline-light">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card bg-secondary text-light">
            <div class="card-header">Daftar User</div>
            <div class="card-body">
                <table class="table table-dark table-striped align-middle">
                    <thead><tr><th>#</th><th>Username</th><th>Phone</th><th>Role</th><th>Dibuat</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $u['role']; ?></span></td>
                            <td><?php echo format_datetime($u['created_at']); ?></td>
                            <td>
                                <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <?php if ($u['id'] !== ($_SESSION['user']['id'] ?? 0)): ?>
                                <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                                <?php endif; ?>
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
