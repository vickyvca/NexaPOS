<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin']);
check_csrf();
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'kasir';
    $password = $_POST['password'] ?? '';
    if ($name && $username) {
        if ($id) {
            if ($password) {
                $stmt = $pdo->prepare("UPDATE users SET name=?, username=?, role=?, password_hash=? WHERE id=?");
                $stmt->execute([$name, $username, $role, password_hash($password, PASSWORD_BCRYPT), $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, username=?, role=? WHERE id=?");
                $stmt->execute([$name, $username, $role, $id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO users(name, username, password_hash, role, created_at) VALUES(?,?,?,?,NOW())");
            $stmt->execute([$name, $username, password_hash($password ?: '123456', PASSWORD_BCRYPT), $role]);
        }
    }
    redirect('/master/user_index.php');
}
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_GET['del']]);
    redirect('/master/user_index.php');
}
$rows = fetch_options('users');
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>User</h4>
<form class="row g-2 mb-3" method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? ''); ?>">
    <?php
    $edit = ['name'=>'','username'=>'','role'=>'kasir'];
    if (!empty($_GET['id'])) foreach ($rows as $r) if ($r['id']==(int)$_GET['id']) $edit=$r;
    ?>
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Nama" required value="<?= htmlspecialchars($edit['name']); ?>"></div>
    <div class="col-md-3"><input class="form-control" name="username" placeholder="Username" required value="<?= htmlspecialchars($edit['username']); ?>"></div>
    <div class="col-md-2">
        <select class="form-select" name="role">
            <?php foreach (['admin','kasir','owner'] as $r): ?>
                <option value="<?= $r; ?>" <?= $edit['role']==$r?'selected':''; ?>><?= ucfirst($r); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><input class="form-control" name="password" placeholder="Password (kosongkan jika tidak ganti)"></div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
</form>
<table class="table table-bordered">
    <tr><th>#</th><th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= $r['id']; ?></td>
        <td><?= htmlspecialchars($r['name']); ?></td>
        <td><?= htmlspecialchars($r['username']); ?></td>
        <td><?= htmlspecialchars($r['role']); ?></td>
        <td>
            <a class="btn btn-sm btn-secondary" href="?id=<?= $r['id']; ?>">Edit</a>
            <a class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')" href="?del=<?= $r['id']; ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
