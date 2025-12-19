<?php
require_once __DIR__ . '/../helpers.php';
if (current_user()) redirect('/index.php');
$pdo = getPDO();

// Seed admin default bila tabel user kosong agar tidak terblokir login
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount === 0) {
    $pdo->prepare("INSERT INTO users(name, username, password_hash, role, created_at) VALUES(?,?,?,?,NOW())")
        ->execute(['Admin', 'admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        redirect('/index.php');
    } else {
        $error = 'Login gagal';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - <?= APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:420px">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3 text-center"><?= APP_NAME; ?> - Login</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                <div class="mb-3">
                    <label>Username</label>
                    <input class="form-control" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
