<?php
// Seeder demo transaksi dan master data minimarket
// Akses via browser setelah login admin: http://localhost/NexaPOS/seed_demo.php?run=1&reset=1
require_once __DIR__ . '/middleware.php';
ensure_role(['admin']);
$pdo = getPDO();
set_time_limit(0);

if (!isset($_GET['run'])) {
    echo "<h3>Seeder Demo NexaPOS</h3>";
    echo "<p>Gunakan parameter run=1 untuk menjalankan, reset=1 untuk truncate data transaksi & master (kecuali users).</p>";
    echo "<p>Contoh: <code>seed_demo.php?run=1&reset=1</code></p>";
    exit;
}

// Reset data jika diminta
if (($_GET['reset'] ?? '0') === '1') {
    $tables = ['purchase_items','purchases','sale_items','sales','stock_moves','returns','return_items','items','categories','suppliers'];
    foreach ($tables as $t) {
        $pdo->exec("DELETE FROM $t");
        $pdo->exec("ALTER TABLE $t AUTO_INCREMENT=1");
    }
}

// Master data
$categories = ['Sembako','Minuman','Snack','Kesehatan','Rokok','Rokok Ecer'];
foreach ($categories as $c) {
    $stmt = $pdo->prepare("INSERT INTO categories(name) VALUES(?)");
    $stmt->execute([$c]);
}

$suppliers = [
    ['Sinar Jaya Grosir','08123456789','Jakarta'],
    ['Berkah Mandiri','08111122334','Bekasi'],
    ['Mega Retail','0813555777','Tangerang'],
];
foreach ($suppliers as $s) {
    $pdo->prepare("INSERT INTO suppliers(name, phone, address) VALUES(?,?,?)")->execute($s);
}

// Items minimarket + rokok eceran
$itemList = [
    ['BRG001','Indomie Goreng', 'Sembako', 'pcs', 2500, 3500],
    ['BRG002','Indomie Ayam Bawang', 'Sembako', 'pcs', 2500, 3500],
    ['BRG003','Beras Ramos 5kg', 'Sembako', 'karung', 52000, 65000],
    ['BRG004','Gula Pasir 1kg', 'Sembako', 'pack', 12000, 15000],
    ['BRG005','Minyak Goreng 1L', 'Sembako', 'btl', 14000, 18000],
    ['BRG006','Telur Ayam (isi 10)', 'Sembako', 'rak', 18000, 23000],
    ['BRG007','Air Mineral 600ml', 'Minuman', 'btl', 2000, 3500],
    ['BRG008','Air Mineral 1.5L', 'Minuman', 'btl', 4000, 6500],
    ['BRG009','Teh Botol Sosro', 'Minuman', 'btl', 3500, 5500],
    ['BRG010','Coca Cola 390ml', 'Minuman', 'btl', 3500, 6000],
    ['BRG011','Pocari Sweat 500ml', 'Minuman', 'btl', 6000, 8500],
    ['BRG012','Kopi Good Day', 'Minuman', 'sachet', 1200, 2000],
    ['BRG013','Kopi Kapal Api 65gr', 'Minuman', 'pack', 6500, 9000],
    ['BRG014','Teh Celup Sariwangi 25', 'Minuman', 'box', 7000, 9500],
    ['BRG015','Chitato 68gr', 'Snack', 'pcs', 8000, 11000],
    ['BRG016','Potabee 68gr', 'Snack', 'pcs', 7500, 10500],
    ['BRG017','Qtela 55gr', 'Snack', 'pcs', 6500, 9000],
    ['BRG018','Taro Net 70gr', 'Snack', 'pcs', 7000, 10000],
    ['BRG019','Silverqueen 58gr', 'Snack', 'pcs', 13000, 16500],
    ['BRG020','Oreo 133gr', 'Snack', 'pcs', 8000, 11000],
    ['BRG021','Masker Medis 50pcs', 'Kesehatan', 'box', 15000, 25000],
    ['BRG022','Hansaplast 10s', 'Kesehatan', 'box', 6000, 9000],
    ['BRG023','Paracetamol 10s', 'Kesehatan', 'strip', 3000, 6000],
    ['BRG024','Vitamin C 30s', 'Kesehatan', 'btl', 15000, 22000],
    ['BRG025','Sabun Lifebuoy 90gr', 'Kesehatan', 'pcs', 2500, 4500],
    ['BRG026','Shampoo Sunsilk 170ml', 'Kesehatan', 'btl', 16000, 21000],
    ['BRG027','Marlboro Merah 20', 'Rokok', 'slop', 32000, 38000],
    ['BRG028','Djarum Super 12', 'Rokok', 'bungkus', 18000, 23000],
    ['BRG029','Gudang Garam Filter 12', 'Rokok', 'bungkus', 17000, 22000],
    ['BRG030','Surya 16', 'Rokok', 'bungkus', 26000, 32000],
    ['BRG031','Sampoerna Mild 16', 'Rokok', 'bungkus', 24000, 30000],
    ['BRG032','Magnum Filter 20', 'Rokok', 'bungkus', 21000, 26000],
    ['BRG033','Rokok Batangan Campuran', 'Rokok Ecer', 'batang', 1200, 1800],
    ['BRG034','Rokok Mild Batangan', 'Rokok Ecer', 'batang', 1300, 2000],
    ['BRG035','Rokok Kretek Batangan', 'Rokok Ecer', 'batang', 1000, 1500],
    ['BRG036','Susu UHT 1L', 'Sembako', 'btl', 12000, 15500],
    ['BRG037','Sosis Kanzler 65gr', 'Snack', 'pcs', 7000, 10000],
    ['BRG038','Roti Tawar 400gr', 'Sembako', 'bks', 11000, 15000],
    ['BRG039','Keju Slice 5s', 'Snack', 'bks', 8000, 11000],
    ['BRG040','Chiki Balls 16gr', 'Snack', 'pcs', 2000, 3500]
];

// Map kategori name => id
$catMap = [];
foreach ($pdo->query("SELECT id,name FROM categories") as $c) {
    $catMap[$c['name']] = $c['id'];
}

$insertItem = $pdo->prepare("INSERT INTO items(code, barcode, name, category_id, unit, buy_price, sell_price, sell_price_lv2, sell_price_lv3, discount_pct, stock, min_stock, is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($itemList as $it) {
    [$code,$name,$cat,$unit,$buy,$sell] = $it;
    $catId = $catMap[$cat] ?? null;
    $sell2 = $sell * 0.98; // sedikit lebih murah
    $sell3 = $sell * 0.95;
    $insertItem->execute([$code,$code,$name,$catId,$unit,$buy,$sell,$sell2,$sell3,0,0,5,1]);
}

// Siapkan stok map
$stocks = [];
foreach ($pdo->query("SELECT id, stock FROM items") as $r) { $stocks[$r['id']] = 0; }

// Fungsi helper
function random_items($count) { return array_rand(range(0,$count-1), rand(1, min(5,$count))); }

// Belanja awal untuk semua item (stok awal besar)
$today = new DateTime();
$baseDate = (clone $today)->modify('-40 days');
$itemRows = $pdo->query("SELECT * FROM items")->fetchAll();
$supplierIds = $pdo->query("SELECT id FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);

$purchaseNo = 1;
$saleNo = 1;

// Function buat pembelian
function create_purchase($pdo, $supplierId, $date, $items, &$stocks, &$purchaseNo) {
    $no = 'PB' . date('ymd', strtotime($date)) . str_pad($purchaseNo++, 3, '0', STR_PAD_LEFT);
    $total = 0;
    $pdo->prepare("INSERT INTO purchases(purchase_no,supplier_id,date,total,status,created_by) VALUES(?,?,?,?,?,?)")
        ->execute([$no, $supplierId, $date, 0, 'posted', 1]);
    $pid = $pdo->lastInsertId();
    $piStmt = $pdo->prepare("INSERT INTO purchase_items(purchase_id,item_id,qty,price,subtotal) VALUES(?,?,?,?,?)");
    $stockMove = $pdo->prepare("INSERT INTO stock_moves(item_id, ref_type, ref_id, date, qty_in, qty_out, note, created_by) VALUES(?,?,?,?,?,?,?,?)");
    foreach ($items as $it) {
        $qty = $it['qty']; $price = $it['price']; $sub = $qty * $price; $total += $sub;
        $piStmt->execute([$pid, $it['id'], $qty, $price, $sub]);
        $pdo->prepare("UPDATE items SET stock = stock + ?, buy_price=? WHERE id=?")->execute([$qty, $price, $it['id']]);
        $stocks[$it['id']] = ($stocks[$it['id']] ?? 0) + $qty;
        $stockMove->execute([$it['id'],'purchase',$pid,$date,$qty,0,'Pembelian',1]);
    }
    $pdo->prepare("UPDATE purchases SET total=? WHERE id=?")->execute([$total,$pid]);
}

// Function buat penjualan
function create_sale($pdo, $date, $items, $payment, $customer, &$stocks, &$saleNo) {
    $no = 'SL' . date('ymd', strtotime($date)) . str_pad($saleNo++, 4, '0', STR_PAD_LEFT);
    $total = 0; $discountTotal = 0; $grand = 0;
    foreach ($items as $it) { $total += $it['subtotal']; $discountTotal += $it['discount']; }
    $grand = $total - $discountTotal;
    $cash = $grand; $change = 0;
    if ($payment === 'cash') { $cash = $grand; $change = 0; }
    $pdo->prepare("INSERT INTO sales(sale_no,date,customer_name,total,discount,grand_total,payment_method,cash_paid,change_amount,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)")
        ->execute([$no,$date,$customer,$total,$discountTotal,$grand,$payment,$cash,$change,1]);
    $sid = $pdo->lastInsertId();
    $siStmt = $pdo->prepare("INSERT INTO sale_items(sale_id,item_id,qty,price,discount,subtotal) VALUES(?,?,?,?,?,?)");
    $stockMove = $pdo->prepare("INSERT INTO stock_moves(item_id, ref_type, ref_id, date, qty_in, qty_out, note, created_by) VALUES(?,?,?,?,?,?,?,?)");
    foreach ($items as $it) {
        $siStmt->execute([$sid,$it['id'],$it['qty'],$it['price'],$it['discount'],$it['subtotal']]);
        $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id=?")->execute([$it['qty'],$it['id']]);
        $stocks[$it['id']] -= $it['qty'];
        $stockMove->execute([$it['id'],'sale',$sid,$date,0,$it['qty'],'Penjualan',1]);
    }
}

// Stok awal per item via pembelian tunggal besar
foreach ($itemRows as $r) {
    create_purchase($pdo, $supplierIds[array_rand($supplierIds)], $baseDate->format('Y-m-d'), [[
        'id'=>$r['id'], 'qty'=>rand(80,150), 'price'=>$r['buy_price']
    ]], $stocks, $purchaseNo);
}

// Generate 30 hari transaksi
for ($d=0; $d<30; $d++) {
    $date = (clone $baseDate)->modify("+" . ($d+1) . " days")->format('Y-m-d');
    // Pembelian harian 2-6 kali
    $purchaseCount = rand(2,6);
    for ($i=0;$i<$purchaseCount;$i++) {
        $pick = array_rand($itemRows, rand(3,7));
        if (!is_array($pick)) $pick = [$pick];
        $itemsPurchase = [];
        foreach ($pick as $idx) {
            $row = $itemRows[$idx];
            $qty = rand(10,60);
            $price = $row['buy_price'] * (rand(95,105)/100);
            $itemsPurchase[] = ['id'=>$row['id'], 'qty'=>$qty, 'price'=>$price];
        }
        create_purchase($pdo, $supplierIds[array_rand($supplierIds)], $date, $itemsPurchase, $stocks, $purchaseNo);
    }

    // Penjualan harian 15-50 trx
    $saleCount = rand(15,50);
    for ($s=0;$s<$saleCount;$s++) {
        $pick = array_rand($itemRows, rand(1,5));
        if (!is_array($pick)) $pick = [$pick];
        $itemsSale = [];
        foreach ($pick as $idx) {
            $row = $itemRows[$idx];
            $qty = rand(1,5);
            // Pastikan stok cukup
            if (($stocks[$row['id']] ?? 0) < $qty) continue;
            $price = $row['sell_price'];
            $discount = 0;
            if ($row['category_id'] && rand(1,10)==1) $discount = $price * 0.05; // diskon 5% sesekali
            $subtotal = ($price * $qty) - $discount;
            $itemsSale[] = ['id'=>$row['id'], 'qty'=>$qty, 'price'=>$price, 'discount'=>$discount, 'subtotal'=>$subtotal];
        }
        if (!$itemsSale) continue;
        $pay = ['cash','transfer','QRIS'][array_rand(['cash','transfer','QRIS'])];
        $cust = 'Cust-' . rand(1000,9999);
        create_sale($pdo, $date, $itemsSale, $pay, $cust, $stocks, $saleNo);
    }
}

echo "Seeder selesai. Total stok tersisa per item di DB. Cek laporan penjualan/pembelian untuk data historis acak 30 hari dengan 15-50 transaksi/hari.";
?>
