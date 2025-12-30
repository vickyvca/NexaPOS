<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$pdo = $GLOBALS['pdo'];

function insert_tariff(PDO $pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO tariffs (name, rate_per_hour, min_minutes, table_id, table_ids, day_of_week, start_time, end_time, block_minutes, block_price, is_default) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $data['name'],
        $data['rate_per_hour'],
        $data['min_minutes'],
        $data['table_id'] ?? null,
        $data['table_ids'] ?? null,
        $data['day_of_week'] ?? null,
        $data['start_time'] ?? null,
        $data['end_time'] ?? null,
        $data['block_minutes'] ?? null,
        $data['block_price'] ?? null,
        $data['is_default'] ?? 0
    ]);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'manual_save':
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $block_minutes = filter_input(INPUT_POST, 'block_minutes', FILTER_VALIDATE_INT);
            $block_price = filter_input(INPUT_POST, 'block_price', FILTER_VALIDATE_INT);
            $rate = ($block_minutes && $block_price) ? (int)ceil($block_price * (60 / $block_minutes)) : (int)($_POST['rate_per_hour'] ?? 0);
            $min_minutes = filter_input(INPUT_POST, 'min_minutes', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $is_default = isset($_POST['is_default']) ? 1 : 0;
            $table_ids = $_POST['table_ids'] ?? [];
            $table_ids_csv = $table_ids ? implode(',', array_map('intval', $table_ids)) : null;
            $day_of_week = ($_POST['day_of_week'] === '') ? null : (int)$_POST['day_of_week'];
            $start_time = empty($_POST['start_time']) ? null : $_POST['start_time'];
            $end_time = empty($_POST['end_time']) ? null : $_POST['end_time'];

            if (empty($name)) throw new Exception('Nama tarif wajib diisi.');

            if ($is_default) {
                $pdo->exec("UPDATE tariffs SET is_default = 0");
            }
            
            if ($id) {
                $stmt = $pdo->prepare("UPDATE tariffs SET name=?, rate_per_hour=?, min_minutes=?, table_ids=?, day_of_week=?, start_time=?, end_time=?, block_minutes=?, block_price=?, is_default=? WHERE id=?");
                $stmt->execute([$name, $rate, $min_minutes, $table_ids_csv, $day_of_week, $start_time, $end_time, $block_minutes, $block_price, $is_default, $id]);
                $_SESSION['flash_success'] = 'Tarif berhasil diperbarui.';
            } else {
                 insert_tariff($pdo, [
                    'name' => $name, 'rate_per_hour' => $rate, 'min_minutes' => $min_minutes,
                    'table_ids' => $table_ids_csv, 'day_of_week' => $day_of_week,
                    'start_time' => $start_time, 'end_time' => $end_time,
                    'block_minutes' => $block_minutes, 'block_price' => $block_price,
                    'is_default' => $is_default
                ]);
                $_SESSION['flash_success'] = 'Tarif manual berhasil ditambahkan.';
            }
            break;

        case 'simple_generate':
            $category = $_POST['category'] ?? 'regular';
            $day_type = $_POST['day_type'] ?? 'all';
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            $block_price = filter_input(INPUT_POST, 'block_price', FILTER_VALIDATE_INT);
            $block_minutes = filter_input(INPUT_POST, 'block_minutes', FILTER_VALIDATE_INT);
            $split_midnight = isset($_POST['split_midnight']);

            if (!$start_time || !$end_time || !$block_price || !$block_minutes) {
                throw new Exception('Semua field generator wajib diisi.');
            }

            $tables = $pdo->query("SELECT id FROM billiard_tables WHERE category = '$category'")->fetchAll(PDO::FETCH_COLUMN);
            if (!$tables) throw new Exception("Tidak ada meja dengan kategori '$category'.");
            $table_ids_csv = implode(',', $tables);
            
            $days_map = ['weekday' => [1,2,3,4], 'weekend' => [5,6,0], 'all' => [0,1,2,3,4,5,6]];
            $day_list = $days_map[$day_type];
            $rate = (int)ceil($block_price * (60 / $block_minutes));

            $name = strtoupper($category) . " " . ucfirst($day_type) . " " . substr($start_time, 0, 2) . "-" . substr($end_time, 0, 2);

            foreach($day_list as $day) {
                if ($split_midnight && $end_time < $start_time) {
                     insert_tariff($pdo, ['name' => $name, 'rate_per_hour' => $rate, 'table_ids' => $table_ids_csv, 'day_of_week' => $day, 'start_time' => $start_time, 'end_time' => '23:59:59', 'block_minutes' => $block_minutes, 'block_price' => $block_price]);
                     insert_tariff($pdo, ['name' => $name, 'rate_per_hour' => $rate, 'table_ids' => $table_ids_csv, 'day_of_week' => ($day + 1) % 7, 'start_time' => '00:00:00', 'end_time' => $end_time, 'block_minutes' => $block_minutes, 'block_price' => $block_price]);
                } else {
                    insert_tariff($pdo, ['name' => $name, 'rate_per_hour' => $rate, 'table_ids' => $table_ids_csv, 'day_of_week' => $day, 'start_time' => $start_time, 'end_time' => $end_time, 'block_minutes' => $block_minutes, 'block_price' => $block_price]);
                }
            }
             $_SESSION['flash_success'] = 'Sejumlah ' . count($day_list) . ' tarif berhasil digenerate.';
            break;

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE tariff_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                 throw new Exception('Tarif tidak bisa dihapus karena sudah dipakai di sesi/transaksi.');
            }
            $pdo->prepare("DELETE FROM tariffs WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = 'Tarif berhasil dihapus.';
            break;
        
        default:
             throw new Exception('Aksi tidak valid.');
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
}

header('Location: tariffs.php');
exit;
