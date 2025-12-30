<?php
require_once __DIR__ . '/../middleware.php';
check_csrf();
$pdo = getPDO();
$categories = fetch_options('categories');
$suppliers = fetch_options('suppliers');
$kw = $pdo->query("SELECT ck.keyword, ck.category_id, c.name cat_name FROM category_keywords ck JOIN categories c ON c.id=ck.category_id")->fetchAll();
$item = ['code'=>'','barcode'=>'','name'=>'','category_id'=>'','unit'=>'pcs','buy_price'=>0,'sell_price'=>0,'sell_price_lv2'=>0,'sell_price_lv3'=>0,'discount_pct'=>0,'stock'=>0,'min_stock'=>0,'is_active'=>1];
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $num = function($key){
        $s = str_replace([' ', "\xC2\xA0"], '', (string)($_POST[$key] ?? '0'));
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/\.(?=\d{3}(\D|$))/', '', $s); // hilangkan pemisah ribuan
        $n = (float)$s;
        return round($n);
    };
    $data = [
        $_POST['code'], $_POST['barcode'], $_POST['name'], $_POST['category_id'] ?: null,
        $_POST['unit'], $num('buy_price'), $num('sell_price'), $num('sell_price_lv2') ?? 0,
        $num('sell_price_lv3') ?? 0, $_POST['discount_pct'] ?? 0, $_POST['stock'],
        $_POST['min_stock'], isset($_POST['is_active']) ? 1 : 0
    ];
    if ($id) {
        $data[] = $id;
        $stmt = $pdo->prepare("UPDATE items SET code=?, barcode=?, name=?, category_id=?, unit=?, buy_price=?, sell_price=?, sell_price_lv2=?, sell_price_lv3=?, discount_pct=?, stock=?, min_stock=?, is_active=? WHERE id=?");
        $stmt->execute($data);
    } else {
        $stmt = $pdo->prepare("INSERT INTO items(code, barcode, name, category_id, unit, buy_price, sell_price, sell_price_lv2, sell_price_lv3, discount_pct, stock, min_stock, is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute($data);
    }
    redirect('/master/barang_index.php');
}
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4><?= $item['id'] ?? null ? 'Edit' : 'Tambah'; ?> Barang</h4>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? ''); ?>">
    <div class="row g-2">
        <div class="col-md-3"><label>Kode</label>
            <div class="input-group">
                <input class="form-control" id="code" name="code" required value="<?= htmlspecialchars($item['code']); ?>">
                <button class="btn btn-secondary" type="button" onclick="generateCode()">Auto</button>
            </div>
            <small class="text-muted">Format: kode supplier + kategori + urut</small>
        </div>
        <div class="col-md-3"><label>Barcode</label><input class="form-control" name="barcode" value="<?= htmlspecialchars($item['barcode']); ?>"></div>
        <div class="col-md-6"><label>Nama</label><input class="form-control" name="name" required value="<?= htmlspecialchars($item['name']); ?>"></div>
        <div class="col-md-3"><label>Kategori</label>
            <select class="form-select" name="category_id">
                <option value="">--</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id']; ?>" <?= $item['category_id']==$c['id']?'selected':''; ?>><?= htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label>Supplier (kode untuk auto)</label>
            <select class="form-select" id="supplier_code_select">
                <option value="">-</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><label>Satuan</label><input class="form-control" name="unit" value="<?= htmlspecialchars($item['unit']); ?>"></div>
        <div class="col-md-2"><label>Harga Beli</label><input type="text" class="form-control rupiah-input" name="buy_price" required value="<?= $item['buy_price']; ?>"></div>
        <div class="col-md-2"><label>Harga Jual Lv1</label><input type="text" class="form-control rupiah-input" name="sell_price" required value="<?= $item['sell_price']; ?>"></div>
        <div class="col-md-2"><label>Harga Jual Lv2</label><input type="text" class="form-control rupiah-input" name="sell_price_lv2" value="<?= $item['sell_price_lv2']; ?>"></div>
        <div class="col-md-2"><label>Harga Jual Lv3</label><input type="text" class="form-control rupiah-input" name="sell_price_lv3" value="<?= $item['sell_price_lv3']; ?>"></div>
        <div class="col-md-2"><label>Diskon % (otomatis)</label><input type="number" step="0.01" class="form-control" name="discount_pct" value="<?= $item['discount_pct']; ?>"></div>
        <div class="col-md-1"><label>Stok</label><input type="number" class="form-control" name="stock" value="<?= $item['stock']; ?>"></div>
        <div class="col-md-2"><label>Stok Min</label><input type="number" class="form-control" name="min_stock" value="<?= $item['min_stock']; ?>"></div>
        <div class="col-md-2"><label>Status</label><div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $item['is_active']?'checked':''; ?>> Aktif
        </div></div>
    </div>
    <button class="btn btn-primary mt-3">Simpan</button>
</form>
<script>
function pad(num,len=2){ return String(num).padStart(len,'0'); }
function generateCode(){
    const cat = document.querySelector('select[name=\"category_id\"]').value || '0';
    const sup = document.getElementById('supplier_code_select').value || '0';
    const nextId = <?= (int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM items")->fetchColumn(); ?>;
    const code = pad(sup,2) + pad(cat,2) + pad(nextId,4);
    document.getElementById('code').value = code;
}
// Rekomendasi kategori dari keyword
const kwMap = <?= json_encode($kw); ?>;
const nameInput = document.querySelector('input[name="name"]');
const catSelect = document.querySelector('select[name="category_id"]');
const badge = document.createElement('div');
badge.className = 'small text-info mt-1';
nameInput.parentElement.appendChild(badge);
function suggest(){
    const name = (nameInput.value||'').toLowerCase();
    let found = null;
    kwMap.forEach(k=>{
        if (name.includes(k.keyword.toLowerCase())) found = k;
    });
    if (found) {
        catSelect.value = found.category_id;
        badge.textContent = 'Rekomendasi: ' + found.cat_name;
    } else {
        badge.textContent = '';
    }
}
nameInput.addEventListener('input', suggest);
suggest();
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
