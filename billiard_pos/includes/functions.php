<?php
require_once __DIR__ . '/../config.php';

function load_company_settings() {
    $file = __DIR__ . '/../company.json';
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            $data += [
                'fonnte_token' => ''
            ];
            return $data;
        }
    }
    return [
        'name' => 'Billiard POS',
        'tagline' => 'Billiard & Snack POS',
        'phone' => '',
        'address' => '',
        'logo' => '',
        'fonnte_token' => '',
        'fonnte_target' => '',
        'maintenance_password' => ''
    ];
}

function check_login($role_required = null) {
    if (empty($_SESSION['user'])) {
        header('Location: /billiard_pos/auth/login.php');
        exit;
    }
    if ($role_required && $_SESSION['user']['role'] !== $role_required) {
        http_response_code(403);
        echo 'Anda tidak punya akses.';
        exit;
    }
    // Enforce shift aktif untuk non-admin
    if (!empty($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin') {
        $current = $_SERVER['SCRIPT_NAME'] ?? '';
        $skip = [
            '/billiard_pos/reports/shift_control.php',
            '/billiard_pos/auth/login.php',
            '/billiard_pos/auth/logout.php'
        ];
        if (!in_array($current, $skip, true)) {
            global $pdo;
            $shift = get_active_shift($pdo, $_SESSION['user']['id']);
            if (!$shift) {
                header('Location: /billiard_pos/reports/shift_control.php?need_shift=1');
                exit;
            }
        }
    }
}

function format_rupiah($number) {
    return 'Rp ' . number_format((int)$number, 0, ',', '.');
}

function format_datetime($datetime) {
    return date('d-m-Y H:i', strtotime($datetime));
}

function human_duration($minutes) {
    $h = floor($minutes / 60);
    $m = $minutes % 60;
    if ($h > 0) {
        return $h . ' jam ' . $m . ' mnt';
    }
    return $m . ' mnt';
}

function get_default_tariff(PDO $pdo) {
    $stmt = $pdo->query("SELECT * FROM tariffs WHERE is_default = 1 LIMIT 1");
    return $stmt->fetch();
}

function get_applicable_tariff(PDO $pdo, $table_id) {
    $dow = (int)date('w'); // 0 (Sunday) - 6 (Saturday)
    $time = date('H:i:s');
    $stmt = $pdo->prepare("
        SELECT t.*,
            (t.table_id IS NOT NULL AND t.table_id = :tid) AS score_table_single,
            (t.table_ids IS NOT NULL AND FIND_IN_SET(:tid_str, t.table_ids)) AS score_table_multi,
            (t.day_of_week IS NOT NULL AND t.day_of_week = :dow) AS score_day,
            (t.start_time IS NOT NULL AND t.end_time IS NOT NULL AND :time BETWEEN t.start_time AND t.end_time) AS score_time
        FROM tariffs t
        WHERE (t.table_id IS NULL OR t.table_id = :tid OR (t.table_ids IS NOT NULL AND FIND_IN_SET(:tid_str, t.table_ids)))
          AND (t.day_of_week IS NULL OR t.day_of_week = :dow)
    ");
    $stmt->execute([':tid' => $table_id, ':tid_str' => $table_id, ':dow' => $dow, ':time' => $time]);
    $tariffs = $stmt->fetchAll();
    if (!$tariffs) return get_default_tariff($pdo);
    usort($tariffs, function($a, $b) {
        $aScore = ($a['score_table_single'] || $a['score_table_multi'] ? 2 : 0)
                + ($a['score_day'] ? 1 : 0)
                + ($a['score_time'] ? 1 : 0)
                + ($a['is_default'] ? 0.1 : 0);
        $bScore = ($b['score_table_single'] || $b['score_table_multi'] ? 2 : 0)
                + ($b['score_day'] ? 1 : 0)
                + ($b['score_time'] ? 1 : 0)
                + ($b['is_default'] ? 0.1 : 0);
        if ($aScore === $bScore) return $a['id'] <=> $b['id'];
        return ($aScore > $bScore) ? -1 : 1;
    });
    return $tariffs[0] ?: get_default_tariff($pdo);
}

function get_running_session(PDO $pdo, $table_id) {
    $stmt = $pdo->prepare("SELECT s.*, t.name AS table_name FROM sessions s JOIN billiard_tables t ON s.table_id = t.id WHERE s.table_id = ? AND s.status = 'running' LIMIT 1");
    $stmt->execute([$table_id]);
    return $stmt->fetch();
}

function calculate_billing($start_time, $rate_per_hour, $min_minutes) {
    $minutes = (int)floor((time() - strtotime($start_time)) / 60);
    if ($minutes < $min_minutes) {
        $minutes = $min_minutes;
    }
    $rate_per_minute = $rate_per_hour / 60;
    if ($minutes <= 60) {
        $total = (int)$rate_per_hour;
    } else {
        $extra = $minutes - 60;
        $total = (int)$rate_per_hour + (int)ceil($extra * $rate_per_minute);
    }
    return ['minutes' => $minutes, 'amount' => $total];
}

function calculate_billing_with_package($start_time, $rate_per_hour, $min_minutes, $package = null, $tariff = null) {
    $minutes = (int)floor((time() - strtotime($start_time)) / 60);
    if ($minutes < $min_minutes) {
        $minutes = $min_minutes;
    }
    $rate_per_minute = $rate_per_hour / 60;
    // override jika tarif punya block harga (misal per 5 menit)
    if ($tariff && !empty($tariff['block_minutes']) && !empty($tariff['block_price'])) {
        $bm = (int)$tariff['block_minutes'];
        $bp = (int)$tariff['block_price'];
        $rate_per_minute = $bp / $bm;
    }
    if ($package) {
        $base = (int)$package['special_price'];
        $extra = max(0, $minutes - (int)$package['duration_minutes']);
        $amount = $base + (int)ceil($extra * $rate_per_minute);
        return ['minutes' => $minutes, 'amount' => $amount];
    }
    if ($minutes <= 60) {
        $amount = (int)$rate_per_hour;
    } else {
        $extra = $minutes - 60;
        $amount = (int)$rate_per_hour + (int)ceil($extra * $rate_per_minute);
    }
    return ['minutes' => $minutes, 'amount' => $amount];
}

function calculate_points($grand_total) {
    // simple: 1 poin per 10.000 rupiah
    return (int)floor($grand_total / 10000);
}

function get_package(PDO $pdo, $package_id) {
    if (!$package_id) return null;
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
    $stmt->execute([$package_id]);
    return $stmt->fetch();
}

function adjust_stock(PDO $pdo, $product_id, $delta) {
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    $stmt->execute([$delta, $product_id]);
}

function get_account_by_type(PDO $pdo, $type) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE type = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
    $stmt->execute([$type]);
    return $stmt->fetch();
}

function get_account_id_for_payment(PDO $pdo, $payment_method) {
    $method = strtolower($payment_method);
    switch ($method) {
        case 'transfer':
        case 'card':
            $type = 'bank';
            break;
        case 'qris':
            $type = 'qris';
            break;
        default:
            $type = 'cash';
    }
    $acc = get_account_by_type($pdo, $type);
    return $acc ? (int)$acc['id'] : null;
}

function add_journal(PDO $pdo, $account_id, $type, $amount, $desc, $ref_type = null, $ref_id = null) {
    $stmt = $pdo->prepare("INSERT INTO journals (account_id, txn_time, type, amount, description, ref_type, ref_id) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$account_id, date('Y-m-d H:i:s'), $type, $amount, $desc, $ref_type, $ref_id]);
}

function active_menu($path) {
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    return (strpos($current, $path) !== false) ? 'active' : '';
}

function get_active_shift(PDO $pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM shift_logs WHERE user_id = ? AND end_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function send_fonnte_notification($target, $message, $tokenOverride = null) {
    $target = preg_replace('/[^0-9+]/', '', $target);
    if (!$target || !$message) return false;
    $settings = load_company_settings();
    $token = $tokenOverride ?: ($settings['fonnte_token'] ?? '');
    if (!$token) return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'target' => $target,
            'message' => $message
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}
?>
