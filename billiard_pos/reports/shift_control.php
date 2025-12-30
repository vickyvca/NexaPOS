<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();

$user_id = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$active = get_active_shift($pdo, $user_id);
$msg = $err = '';
$needShift = isset($_GET['need_shift']);
if ($needShift && !$active) {
    $err = 'Mulai shift dulu sebelum bekerja.';
}

$default_shifts = [
    'Shift 1 (11:00-19:00)' => ['start' => '11:00', 'end' => '19:00'],
    'Shift 2 (19:00-03:00)' => ['start' => '19:00', 'end' => '03:00']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        if ($active) {
            $err = 'Anda masih punya shift aktif.';
        } else {
            $shift_name = $_POST['shift_name'] ?? 'Shift';
            $pdo->prepare("INSERT INTO shift_logs (user_id, shift_name, start_time) VALUES (?,?,NOW())")->execute([$user_id, $shift_name]);
            $active = get_active_shift($pdo, $user_id);
            // notif WA
            $settings = load_company_settings();
            if (!empty($settings['fonnte_target'])) {
                $text = "Shift dimulai\nUser: {$username}\nShift: {$shift_name}\nMulai: " . date('d-m-Y H:i');
                send_fonnte_notification($settings['fonnte_target'], $text);
            }
            $msg = 'Shift dimulai.';
        }
    } elseif ($action === 'stop' && $active) {
        $pdo->prepare("UPDATE shift_logs SET end_time = NOW() WHERE id = ?")->execute([$active['id']]);
        $start = $active['start_time'];
        $end = date('Y-m-d H:i:s');
        // Hitung ringkasan khusus user
        $stmt = $pdo->prepare("SELECT SUM(grand_total) grand, SUM(subtotal) pos, SUM(discount_amount) disc, SUM(extra_charge_amount) extra FROM orders WHERE operator_id = ? AND is_paid=1 AND order_time BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start, $end]);
        $sum = $stmt->fetch();
        $grand = (int)($sum['grand'] ?? 0);
        $pos = (int)($sum['pos'] ?? 0);
        $extra = (int)($sum['extra'] ?? 0);
        $disc = (int)($sum['disc'] ?? 0);
        $billing = $grand - $pos + $disc - $extra;
        $settings = load_company_settings();
        if (!empty($settings['fonnte_target'])) {
            $text = "Shift selesai\nUser: {$username}\nShift: {$active['shift_name']}\nPeriode: ".date('d-m H:i', strtotime($start))." - ".date('d-m H:i', strtotime($end))."\nBilling: ".format_rupiah($billing)."\nPOS: ".format_rupiah($pos)."\nExtra: ".format_rupiah($extra)."\nGrand: ".format_rupiah($grand);
            send_fonnte_notification($settings['fonnte_target'], $text);
        }
        $msg = 'Shift ditutup.';
        $active = null;
        header('Location: /billiard_pos/auth/logout.php');
        exit;
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Shift Pegawai</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($msg): ?><div class="alert alert-success py-2"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?php echo $err; ?></div><?php endif; ?>

<div class="card bg-secondary text-light mb-3">
    <div class="card-body">
        <?php if ($active): ?>
            <div class="mb-3">Shift aktif: <strong><?php echo htmlspecialchars($active['shift_name']); ?></strong> (<?php echo format_datetime($active['start_time']); ?>)</div>
            <form method="post">
                <input type="hidden" name="action" value="stop">
                <button class="btn btn-danger">Tutup Shift & Kirim Rekap</button>
            </form>
        <?php else: ?>
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="start">
                <div class="col-md-6">
                    <label class="form-label">Pilih Shift</label>
                    <select name="shift_name" class="form-select">
                        <?php foreach ($default_shifts as $name => $t): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-success w-100">Mulai Shift</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
