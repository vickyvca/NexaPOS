<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

// Defaults
$success = $error = '';
$tables = $pdo->query("SELECT id, name, category FROM billiard_tables ORDER BY id")->fetchAll();
$editData = null;

// Quick helper to insert one tariff
function insert_tariff(PDO $pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO tariffs (name, rate_per_hour, min_minutes, table_id, table_ids, day_of_week, start_time, end_time, block_minutes, block_price, is_default) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $data['name'],
        $data['rate_per_hour'],
        $data['min_minutes'],
        $data['table_id'],
        $data['table_ids'],
        $data['day_of_week'],
        $data['start_time'],
        $data['end_time'],
        $data['block_minutes'],
        $data['block_price'],
        $data['is_default']
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form sederhana: kategori + hari + slot siang/malam
    if (isset($_POST['simple_generate'])) {
        $cat = $_POST['simple_category'] ?? 'regular';
        $dayType = $_POST['simple_day'] ?? 'all';
        $slotLabel = $_POST['slot_label'] ?? 'siang';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $block_minutes = (int)($_POST['block_minutes'] ?? 5);
        $block_price = (int)($_POST['block_price'] ?? 0);
        $package_price = (int)($_POST['package_price'] ?? 0);
        $min_minutes = (int)($_POST['min_minutes'] ?? 0);
        $simple_split = isset($_POST['simple_split']);
        $with_package = isset($_POST['with_package']);
        $custom_tables = $_POST['simple_table_ids'] ?? [];
        $daysMap = [
            'weekday' => [1,2,3,4],
            'weekend' => [5,6,0],
            'all'     => [0,1,2,3,4,5,6]
        ];
        $dayList = $daysMap[$dayType] ?? [0,1,2,3,4,5,6];

        // Tentukan meja target
        $targetIds = [];
        if ($cat === 'custom' && !empty($custom_tables)) {
            $targetIds = array_map('intval', $custom_tables);
        } else {
            foreach ($tables as $tb) {
                if (($tb['category'] ?? 'regular') === $cat) {
                    $targetIds[] = (int)$tb['id'];
                }
            }
        }
        $table_ids_csv = $targetIds ? implode(',', $targetIds) : null;

        if ($block_minutes <= 0 || $block_price <= 0) {
            $error = 'Isi harga per blok dan durasi blok.';
        } elseif (!$start_time || !$end_time) {
            $error = 'Jam mulai dan selesai wajib diisi.';
        } else {
            $rate = (int)ceil($block_price * (60 / $block_minutes));
            $name_base = trim($_POST['name_base'] ?? '');
            $slotTitle = ucfirst($slotLabel);
            $dayTitle = ($dayType === 'weekday') ? 'Weekday' : (($dayType === 'weekend') ? 'Weekend' : 'AllDay');
            if ($name_base === '') {
                $name_base = strtoupper($cat) . ' ' . $dayTitle . ' ' . $slotTitle;
            }
            $entries = [];
            foreach ($dayList as $d) {
                $splitNeeded = $simple_split || ($end_time < $start_time);
                if ($splitNeeded) {
                    $entries[] = [
                        'name' => $name_base,
                        'rate_per_hour' => $rate,
                        'min_minutes' => $min_minutes,
                        'table_ids' => $table_ids_csv,
                        'day_of_week' => $d,
                        'start_time' => $start_time,
                        'end_time' => '23:59:59',
                        'block_minutes' => $block_minutes,
                        'block_price' => $block_price
                    ];
                    $entries[] = [
                        'name' => $name_base,
                        'rate_per_hour' => $rate,
                        'min_minutes' => $min_minutes,
                        'table_ids' => $table_ids_csv,
                        'day_of_week' => ($d + 1) % 7,
                        'start_time' => '00:00:00',
                        'end_time' => $end_time,
                        'block_minutes' => $block_minutes,
                        'block_price' => $block_price
                    ];
                } else {
                    $entries[] = [
                        'name' => $name_base,
                        'rate_per_hour' => $rate,
                        'min_minutes' => $min_minutes,
                        'table_ids' => $table_ids_csv,
                        'day_of_week' => $d,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'block_minutes' => $block_minutes,
                        'block_price' => $block_price
                    ];
                }
                if ($with_package && $package_price > 0) {
                    $pkgRate = (int)ceil($package_price / 3.0); // kisaran per jam paket
                    $pkgEntry = [
                        'name' => $name_base . ' Paket 3j',
                        'rate_per_hour' => $pkgRate,
                        'min_minutes' => 180,
                        'table_ids' => $table_ids_csv,
                        'day_of_week' => $d,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'block_minutes' => 180,
                        'block_price' => $package_price
                    ];
                    if ($splitNeeded) {
                        $pkgEntry['end_time'] = '23:59:59';
                        $entries[] = $pkgEntry;
                        $pkgEntry['day_of_week'] = ($d + 1) % 7;
                        $pkgEntry['start_time'] = '00:00:00';
                        $pkgEntry['end_time'] = $end_time;
                        $entries[] = $pkgEntry;
                    } else {
                        $entries[] = $pkgEntry;
                    }
                }
            }
            try {
                foreach ($entries as $e) {
                    insert_tariff($pdo, [
                        'name' => $e['name'],
                        'rate_per_hour' => $e['rate_per_hour'],
                        'min_minutes' => $e['min_minutes'],
                        'table_id' => null,
                        'table_ids' => $e['table_ids'],
                        'day_of_week' => $e['day_of_week'],
                        'start_time' => $e['start_time'],
                        'end_time' => $e['end_time'],
                        'block_minutes' => $e['block_minutes'],
                        'block_price' => $e['block_price'],
                        'is_default' => 0
                    ]);
                }
                $success = 'Tarif sederhana berhasil dibuat (' . count($entries) . ' baris).';
            } catch (Exception $e) {
                $error = 'Gagal simpan: '.$e->getMessage();
            }
        }
    }
    // Generator cepat VIP/REG
    else if (isset($_POST['generate_preset'])) {
        $vip_ids = trim($_POST['vip_table_ids'] ?? '');
        $reg_ids = trim($_POST['reg_table_ids'] ?? '');
        if ($vip_ids === '' || $reg_ids === '') {
            $error = 'Isi Table ID VIP dan Regular.';
        } else {
            $presets = [];
            $add = function($name, $ids, $dow, $start, $end, $block, $price, $min = 0) use (&$presets) {
                $rate = ($block && $price) ? (int)ceil($price * (60 / $block)) : 0;
                $presets[] = [
                    'name' => $name,
                    'rate_per_hour' => $rate,
                    'min_minutes' => $min,
                    'table_id' => null,
                    'table_ids' => $ids,
                    'day_of_week' => $dow,
                    'start_time' => $start,
                    'end_time' => $end,
                    'block_minutes' => $block ?: null,
                    'block_price' => $price ?: null,
                    'is_default' => 0
                ];
            };
            // VIP Senin-Kamis (1-4)
            foreach ([1,2,3,4] as $d) {
                $add('VIP WKD Siang', $vip_ids, $d, '11:00:00', '17:00:00', 5, 2500);
                $add('VIP WKD Malam1', $vip_ids, $d, '17:00:00', '23:59:59', 5, 3000);
                $add('VIP WKD Malam2', $vip_ids, ($d+1)%7, '00:00:00', '03:00:00', 5, 3000);
                // Paket 3 jam (siang 60k, malam 90k)
                $add('VIP WKD Paket Siang 3j', $vip_ids, $d, '11:00:00', '17:00:00', 180, 60000, 180);
                $add('VIP WKD Paket Malam 3j', $vip_ids, $d, '17:00:00', '23:59:59', 180, 90000, 180);
                $add('VIP WKD Paket Malam 3j (Lanjut)', $vip_ids, ($d+1)%7, '00:00:00', '03:00:00', 180, 90000, 180);
            }
            // VIP Jumat-Minggu (5,6,0)
            foreach ([5,6,0] as $d) {
                $add('VIP WKE Siang', $vip_ids, $d, '11:00:00', '17:00:00', 5, 3000);
                $add('VIP WKE Malam1', $vip_ids, $d, '17:00:00', '23:59:59', 5, 3500);
                $add('VIP WKE Malam2', $vip_ids, ($d+1)%7, '00:00:00', '03:00:00', 5, 3500);
                // Paket 3 jam (siang 75k, malam 95k)
                $add('VIP WKE Paket Siang 3j', $vip_ids, $d, '11:00:00', '17:00:00', 180, 75000, 180);
                $add('VIP WKE Paket Malam 3j', $vip_ids, $d, '17:00:00', '23:59:59', 180, 95000, 180);
                $add('VIP WKE Paket Malam 3j (Lanjut)', $vip_ids, ($d+1)%7, '00:00:00', '03:00:00', 180, 95000, 180);
            }
            // REG Senin-Kamis (1-4)
            foreach ([1,2,3,4] as $d) {
                $add('REG WKD Siang', $reg_ids, $d, '11:00:00', '17:00:00', 5, 1700);
                $add('REG WKD Malam1', $reg_ids, $d, '17:00:00', '23:59:59', 5, 2100);
                $add('REG WKD Malam2', $reg_ids, ($d+1)%7, '00:00:00', '03:00:00', 5, 2100);
                $add('REG WKD Paket Siang 3j', $reg_ids, $d, '11:00:00', '17:00:00', 180, 50000, 180);
                $add('REG WKD Paket Malam 3j', $reg_ids, $d, '17:00:00', '23:59:59', 180, 60000, 180);
                $add('REG WKD Paket Malam 3j (Lanjut)', $reg_ids, ($d+1)%7, '00:00:00', '03:00:00', 180, 60000, 180);
            }
            // REG Jumat-Minggu (5,6,0)
            foreach ([5,6,0] as $d) {
                $add('REG WKE Siang', $reg_ids, $d, '11:00:00', '17:00:00', 5, 2100);
                $add('REG WKE Malam1', $reg_ids, $d, '17:00:00', '23:59:59', 5, 2500);
                $add('REG WKE Malam2', $reg_ids, ($d+1)%7, '00:00:00', '03:00:00', 5, 2500);
                $add('REG WKE Paket Siang 3j', $reg_ids, $d, '11:00:00', '17:00:00', 180, 60000, 180);
                $add('REG WKE Paket Malam 3j', $reg_ids, $d, '17:00:00', '23:59:59', 180, 70000, 180);
                $add('REG WKE Paket Malam 3j (Lanjut)', $reg_ids, ($d+1)%7, '00:00:00', '03:00:00', 180, 70000, 180);
            }
            // Paket Pelajar (hanya meja reguler)
            foreach ([1,2,3,4,5,6,0] as $d) { // semua hari
                $add('Paket Pelajar Siang 3j', $reg_ids, $d, '11:00:00', '17:00:00', 180, 50000, 180);
                $add('Paket Pelajar Malam 3j', $reg_ids, $d, '17:00:00', '23:59:59', 180, 60000, 180);
                $add('Paket Pelajar Malam 3j (Lanjut)', $reg_ids, ($d+1)%7, '00:00:00', '03:00:00', 180, 60000, 180);
            }
            $pdo->beginTransaction();
            try {
                foreach ($presets as $p) {
                    insert_tariff($pdo, $p);
                }
                $pdo->commit();
                $success = 'Preset tarif sesuai skema baru berhasil dibuat.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal generate: '.$e->getMessage();
            }
        }
    } else {
        // CRUD manual
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $rate = (int)($_POST['rate_per_hour'] ?? 0);
        $block_minutes = (int)($_POST['block_minutes'] ?? 0);
        $block_price = (int)($_POST['block_price'] ?? 0);
        $min_minutes = (int)($_POST['min_minutes'] ?? 0);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $table_ids = $_POST['table_ids'] ?? [];
        $table_ids_csv = $table_ids ? implode(',', array_map('intval', $table_ids)) : null;
        $start_time = $_POST['start_time'] ?: null;
        $end_time = $_POST['end_time'] ?: null;
        $day_of_week_single = ($_POST['day_of_week'] ?? '') === '' ? null : (int)$_POST['day_of_week'];
        $daysInput = [];
        if (!empty($_POST['days']) && is_array($_POST['days'])) {
            $daysInput = array_map('intval', $_POST['days']);
        } elseif ($day_of_week_single !== null) {
            $daysInput = [$day_of_week_single];
        } else {
            $daysInput = [null];
        }

        if ($block_minutes > 0 && $block_price > 0) {
            $rate = (int)ceil($block_price * (60 / $block_minutes));
        }
        if ($name === '') {
            $error = 'Nama wajib diisi.';
        } else {
            if ($is_default) {
                $pdo->exec("UPDATE tariffs SET is_default = 0");
                $table_ids_csv = null;
                $start_time = null;
                $end_time = null;
                $daysInput = [null];
            }
            if ($id) {
                $stmt = $pdo->prepare("UPDATE tariffs SET name=?, rate_per_hour=?, min_minutes=?, table_ids=?, day_of_week=?, start_time=?, end_time=?, block_minutes=?, block_price=?, is_default=? WHERE id=?");
                $stmt->execute([$name, $rate, $min_minutes, $table_ids_csv, $day_of_week_single, $start_time, $end_time, $block_minutes ?: null, $block_price ?: null, $is_default, $id]);
                $success = 'Tarif diperbarui.';
            } else {
                $split = isset($_POST['split_midnight']) && $_POST['split_midnight'] == '1';
                foreach ($daysInput as $d) {
                    if ($split && $start_time && $end_time) {
                        insert_tariff($pdo, [
                            'name' => $name,
                            'rate_per_hour' => $rate,
                            'min_minutes' => $min_minutes,
                            'table_id' => null,
                            'table_ids' => $table_ids_csv,
                            'day_of_week' => $d,
                            'start_time' => $start_time,
                            'end_time' => '23:59:59',
                            'block_minutes' => $block_minutes ?: null,
                            'block_price' => $block_price ?: null,
                            'is_default' => $is_default
                        ]);
                        insert_tariff($pdo, [
                            'name' => $name,
                            'rate_per_hour' => $rate,
                            'min_minutes' => $min_minutes,
                            'table_id' => null,
                            'table_ids' => $table_ids_csv,
                            'day_of_week' => is_null($d) ? null : (($d + 1) % 7),
                            'start_time' => '00:00:00',
                            'end_time' => $end_time,
                            'block_minutes' => $block_minutes ?: null,
                            'block_price' => $block_price ?: null,
                            'is_default' => $is_default
                        ]);
                    } else {
                        insert_tariff($pdo, [
                            'name' => $name,
                            'rate_per_hour' => $rate,
                            'min_minutes' => $min_minutes,
                            'table_id' => null,
                            'table_ids' => $table_ids_csv,
                            'day_of_week' => $d,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'block_minutes' => $block_minutes ?: null,
                            'block_price' => $block_price ?: null,
                            'is_default' => $is_default
                        ]);
                    }
                }
                $success = 'Tarif ditambahkan.';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE tariff_id = ?");
    $stmt->execute([$id]);
    $inuse = (int)$stmt->fetchColumn();
    if ($inuse > 0) {
        $error = 'Tarif tidak bisa dihapus karena masih dipakai di sesi/riwayat.';
    } else {
        $pdo->prepare("DELETE FROM tariffs WHERE id = ?")->execute([$id]);
        $success = 'Tarif dihapus.';
    }
}

$tariffs = $pdo->query("SELECT t.*, bt.name AS table_name FROM tariffs t LEFT JOIN billiard_tables bt ON t.table_id = bt.id ORDER BY t.id ASC")->fetchAll();
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Master Tarif</h4>
    <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row">
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header">
                <div class="nav nav-tabs card-header-tabs" role="tablist">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-simple" type="button" role="tab">Form Sederhana</button>
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-manual" type="button" role="tab"><?php echo $editData ? 'Edit Tarif' : 'Tambah Tarif'; ?></button>
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-preset" type="button" role="tab">Generate VIP/REG</button>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-simple" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="simple_generate" value="1">
                            <div class="mb-2">
                                <label class="form-label">Kategori Meja</label>
                                <select name="simple_category" class="form-select" id="simple_category">
                                    <option value="vip">VIP</option>
                                    <option value="regular" selected>Regular</option>
                                    <option value="vvip">VVIP</option>
                                    <option value="student">Pelajar</option>
                                    <option value="custom">Custom (pilih manual)</option>
                                </select>
                            </div>
                            <div class="mb-2" id="wrap_custom_tables" style="display:none;">
                                <label class="form-label">Pilih Meja (custom)</label>
                                <select name="simple_table_ids[]" class="form-select" multiple size="5">
                                    <?php foreach ($tables as $tb): ?>
                                        <option value="<?php echo $tb['id']; ?>"><?php echo htmlspecialchars($tb['name'] . ' (' . ($tb['category'] ?? 'n/a') . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Gunakan saat kategori = custom.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Jenis Hari</label>
                                    <select name="simple_day" class="form-select">
                                        <option value="weekday">Weekday (Sen-Kam)</option>
                                        <option value="weekend">Weekend (Jum-Mgg)</option>
                                        <option value="all" selected>Semua Hari</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Slot</label>
                                    <select name="slot_label" class="form-select" id="slot_label">
                                        <option value="siang" selected>Siang</option>
                                        <option value="malam">Malam</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="time" name="start_time" id="simple_start" class="form-control" value="11:00">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="time" name="end_time" id="simple_end" class="form-control" value="17:00">
                                </div>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="simple_split" id="simple_split">
                                <label class="form-check-label" for="simple_split">Bagi lintas tengah malam</label>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Harga per blok</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="block_price" class="form-control" value="3000" required>
                                    </div>
                                    <small class="text-muted">Blok menit default 5.</small>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Blok menit</label>
                                    <input type="number" name="block_minutes" class="form-control" value="5">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Min. menit</label>
                                    <input type="number" name="min_minutes" class="form-control" value="0">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Nama (opsional)</label>
                                    <input type="text" name="name_base" class="form-control" placeholder="kosongkan: auto">
                                </div>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="with_package" id="with_package" checked>
                                <label class="form-check-label" for="with_package">Tambahkan Paket 3 Jam</label>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Harga Paket 3 Jam</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="package_price" class="form-control" value="60000">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-end">
                                    <small class="text-muted">Paket otomatis jadi min. 180 menit.</small>
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 mt-2">Buat Tarif</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-manual" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editData['id'] ?? ''); ?>">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editData['name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tarif per Jam</label>
                                <input type="number" name="rate_per_hour" class="form-control" value="<?php echo htmlspecialchars($editData['rate_per_hour'] ?? ''); ?>" placeholder="Otomatis jika isi per blok">
                                <small class="text-muted">Isi manual atau otomatis dari blok per menit.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Blok menit</label>
                                    <input type="number" name="block_minutes" class="form-control" value="<?php echo htmlspecialchars($editData['block_minutes'] ?? 5); ?>" placeholder="misal 5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Harga per blok</label>
                                    <input type="number" name="block_price" class="form-control" value="<?php echo htmlspecialchars($editData['block_price'] ?? ''); ?>" placeholder="misal 2500">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Min. Menit</label>
                                <input type="number" name="min_minutes" class="form-control" value="<?php echo htmlspecialchars($editData['min_minutes'] ?? 0); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Berlaku untuk Meja (opsional)</label>
                                <select name="table_ids[]" class="form-select" multiple size="6">
                                    <?php
                                        $selectedCsv = $editData['table_ids'] ?? '';
                                        $selectedArr = $selectedCsv ? explode(',', $selectedCsv) : [];
                                    ?>
                                    <?php foreach ($tables as $tb): ?>
                                        <option value="<?php echo $tb['id']; ?>" <?php echo in_array($tb['id'], $selectedArr) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tb['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hari (pilih banyak)</label>
                                <?php $dow = $editData['day_of_week'] ?? ''; ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php $dayLabels = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']; ?>
                                    <?php for ($i=0; $i<=6; $i++): ?>
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input" name="days[]" value="<?php echo $i; ?>" <?php echo ($dow === $i || $dow === (string)$i) ? 'checked' : ''; ?>>
                                            <?php echo $dayLabels[$i]; ?>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="time" name="start_time" class="form-control" value="<?php echo htmlspecialchars($editData['start_time'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="time" name="end_time" class="form-control" value="<?php echo htmlspecialchars($editData['end_time'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="split_midnight" id="split_midnight">
                                        <label class="form-check-label" for="split_midnight">Bagi lintas tengah malam</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" name="is_default" id="is_default" <?php echo (($editData['is_default'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_default">Jadikan default</label>
                            </div>
                            <button type="submit" class="btn btn-success">Simpan</button>
                            <?php if ($editData): ?><a href="tariffs.php" class="btn btn-outline-light">Batal</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-preset" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="generate_preset" value="1">
                            <div class="mb-3">
                                <label class="form-label">Table IDs VIP (pisah koma)</label>
                                <input type="text" name="vip_table_ids" class="form-control" placeholder="misal: 1,2,3" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Table IDs Regular (pisah koma)</label>
                                <input type="text" name="reg_table_ids" class="form-control" placeholder="misal: 4,5,6,7" required>
                            </div>
                            <small class="text-muted d-block mb-2">Membuat tarif siang/malam, weekday/weekend, blok 5 menit.</small>
                            <button class="btn btn-warning">Generate Tarif</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header">Daftar Tarif</div>
            <div class="card-body p-0">
                <div class="table-responsive table-scroll">
                    <table class="table table-dark table-striped mb-0 data-table">
                        <thead><tr><th>#</th><th>Nama</th><th>Rate</th><th>Min</th><th>Meja/Hari/Jam</th><th>Default</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($tariffs as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td><?php echo format_rupiah($t['rate_per_hour']); ?><?php if ($t['block_minutes'] && $t['block_price']): ?><br><small>Per <?php echo $t['block_minutes']; ?> mnt: <?php echo format_rupiah($t['block_price']); ?></small><?php endif; ?></td>
                                <td><?php echo $t['min_minutes']; ?> mnt</td>
                                <td class="small">
                                    <?php
                                        $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                                        $tableLabel = 'Semua';
                                        if (!empty($t['table_ids'])) {
                                            $names = [];
                                            $ids = explode(',', $t['table_ids']);
                                            foreach ($tables as $tb) {
                                                if (in_array($tb['id'], $ids)) $names[] = $tb['name'];
                                            }
                                            $tableLabel = implode(', ', $names);
                                        } elseif (!empty($t['table_name'])) {
                                            $tableLabel = $t['table_name'];
                                        }
                                        echo htmlspecialchars($tableLabel);
                                    ?><br>
                                    <?php echo is_null($t['day_of_week']) ? 'Semua hari' : $days[$t['day_of_week']]; ?><br>
                                    <?php echo ($t['start_time'] && $t['end_time']) ? substr($t['start_time'],0,5) . ' - ' . substr($t['end_time'],0,5) : '24 jam'; ?>
                                </td>
                                <td><?php echo $t['is_default'] ? 'Ya' : '-'; ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $t['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus tarif?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slot = document.getElementById('slot_label');
    const st = document.getElementById('simple_start');
    const et = document.getElementById('simple_end');
    const split = document.getElementById('simple_split');
    const catSel = document.getElementById('simple_category');
    const wrapCustom = document.getElementById('wrap_custom_tables');

    if (slot) {
        slot.addEventListener('change', () => {
            if (slot.value === 'siang') {
                st.value = '11:00';
                et.value = '17:00';
                split.checked = false;
            } else if (slot.value === 'malam') {
                st.value = '17:00';
                et.value = '03:00';
                split.checked = true;
            }
        });
    }
    if (catSel && wrapCustom) {
        const toggleCustom = () => { wrapCustom.style.display = (catSel.value === 'custom') ? 'block' : 'none'; };
        catSel.addEventListener('change', toggleCustom);
        toggleCustom();
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
