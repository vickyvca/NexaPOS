<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function log_error(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
}
function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!$token || $token !== ($_SESSION['csrf'] ?? '')) {
            die('Invalid CSRF token');
        }
    }
}
function format_rupiah($n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $user;
    if ($user) return $user;
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}
function require_login(): void {
    if (!current_user()) {
        redirect('/auth/login.php');
    }
}
function has_role($roles): bool {
    $user = current_user();
    if (!$user) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'], $roles, true);
}
function ensure_role($roles): void {
    if (!has_role($roles)) {
        die('Unauthorized');
    }
}
function get_license_key(): string {
    if (file_exists(LICENSE_FILE)) {
        return trim((string)file_get_contents(LICENSE_FILE));
    }
    return '';
}
function validate_license(string $key): array {
    $key = trim($key);
    if ($key === '') return ['ok'=>false,'msg'=>'Lisensi kosong'];
    $parts = explode('-', $key);
    if (count($parts) < 4) return ['ok'=>false,'msg'=>'Format lisensi salah'];
    [$prefix,$type,$exp,$sig] = [$parts[0],$parts[1],$parts[2],$parts[3]];
    if ($prefix !== 'NEXA') return ['ok'=>false,'msg'=>'Prefix salah'];
    $payload = $prefix.'-'.$type.'-'.$exp;
    $calc = substr(hash_hmac('sha256', $payload, LICENSE_SECRET),0,12);
    if (!hash_equals($calc, $sig)) return ['ok'=>false,'msg'=>'Signature salah'];
    if ($type === 'TRIAL') {
        if ($exp !== 'NA' && $exp < date('Ymd')) return ['ok'=>false,'msg'=>'Lisensi trial kedaluwarsa'];
    }
    return ['ok'=>true,'type'=>$type,'exp'=>$exp];
}
function require_license(): void {
    $key = get_license_key();
    $res = validate_license($key);
    if (!$res['ok']) {
        header('Location: '.BASE_URL.'/license.php');
        exit;
    }
}
function fetch_options($table): array {
    $pdo = getPDO();
    return $pdo->query("SELECT id, name FROM {$table} ORDER BY name")->fetchAll();
}
function handle_search_pagination(string $table, array $columns, int $perPage = 10): array {
    $pdo = getPDO();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $search = trim($_GET['q'] ?? '');
    $params = [];
    $where = '';
    if ($search !== '') {
        $likes = [];
        foreach ($columns as $col) {
            $likes[] = "$col LIKE ?";
            $params[] = "%{$search}%";
        }
        $where = "WHERE " . implode(' OR ', $likes);
    }
    $count = $pdo->prepare("SELECT COUNT(*) FROM {$table} {$where}");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'search' => $search];
}
?>
