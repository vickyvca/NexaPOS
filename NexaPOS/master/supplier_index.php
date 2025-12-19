<?php
require_once __DIR__ . '/../middleware.php';
check_csrf();
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$name, $phone, $address, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO suppliers(name, phone, address) VALUES(?,?,?)");
            $stmt->execute([$name, $phone, $address]);
        }
    }
    redirect('/master/supplier_index.php');
}
if (isset($_GET['del'])) {
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id=?");
    $stmt->execute([$_GET['del']]);
    redirect('/master/supplier_index.php');
}
$rows = fetch_options('suppliers');
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Supplier</h4>
<form class="row g-2 mb-3" method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? ''); ?>">
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Nama" required value="<?php
        if (!empty($_GET['id'])) { foreach ($rows as $r) if ($r['id']==(int)$_GET['id']) echo htmlspecialchars($r['name']); } ?>"></div>
    <div class="col-md-3"><input class="form-control" name="phone" placeholder="HP" value="<?php
        if (!empty($_GET['id'])) { foreach ($rows as $r) if ($r['id']==(int)$_GET['id']) echo htmlspecialchars($r['phone']); } ?>"></div>
    <div class="col-md-4"><input class="form-control" name="address" placeholder="Alamat" value="<?php
        if (!empty($_GET['id'])) { foreach ($rows as $r) if ($r['id']==(int)$_GET['id']) echo htmlspecialchars($r['address']); } ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div>
</form>
<table class="table table-bordered">
    <tr><th>#</th><th>Nama</th><th>HP</th><th>Alamat</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= $r['id']; ?></td>
        <td><?= htmlspecialchars($r['name']); ?></td>
        <td><?= htmlspecialchars($r['phone']); ?></td>
        <td><?= htmlspecialchars($r['address']); ?></td>
        <td>
            <a class="btn btn-sm btn-secondary" href="?id=<?= $r['id']; ?>">Edit</a>
            <a class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')" href="?del=<?= $r['id']; ?>">Hapus</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../layout/footer.php'; ?>
