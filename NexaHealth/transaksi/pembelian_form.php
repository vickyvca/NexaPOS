<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
check_csrf();
$pdo = getPDO();
$suppliers = fetch_options('suppliers');
$items = $pdo->query("SELECT i.id,i.code,i.barcode,i.name,i.buy_price,
    (SELECT pi.price FROM purchase_items pi JOIN purchases p ON p.id=pi.purchase_id WHERE pi.item_id=i.id ORDER BY p.date DESC, pi.id DESC LIMIT 1) AS last_price
    FROM items i WHERE is_active=1 ORDER BY name")->fetchAll();
$purchase = ['id'=>'','purchase_no'=>'','supplier_id'=>'','date'=>date('Y-m-d'),'total'=>0,'status'=>'draft'];
$details = [];
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $purchase = $stmt->fetch();
    $det = $pdo->prepare("SELECT pi.*, i.name FROM purchase_items pi JOIN items i ON i.id=pi.item_id WHERE purchase_id=?");
    $det->execute([$purchase['id']]);
    $details = $det->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $money = function($v){
        $s = str_replace([' ', "\xC2\xA0"], '', (string)$v);
        $s = str_replace(',', '.', $s);
        // hapus pemisah ribuan (titik diikuti 3 digit)
        $s = preg_replace('/\.(?=\d{3}(\D|$))/', '', $s);
        $n = (float)$s;
        return round($n);
    };
    $pdo->beginTransaction();
    try {
        $pid = $_POST['id'] ?? '';
        $totalVal = $money($_POST['total']);
        $purchaseNo = $pid ? $_POST['purchase_no'] : 'PB' . date('ymdHis');
        if ($pid) {
            $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id=?");
            $stmt->execute([$pid]);
            $stmt = $pdo->prepare("UPDATE purchases SET supplier_id=?, date=?, total=?, status=?, purchase_no=? WHERE id=?");
            $stmt->execute([$_POST['supplier_id'] ?: null, $_POST['date'], $totalVal, $_POST['status'], $purchaseNo, $pid]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO purchases(purchase_no,supplier_id,date,total,status,created_by) VALUES(?,?,?,?,?,?)");
            $stmt->execute([$purchaseNo, $_POST['supplier_id'] ?: null, $_POST['date'], $totalVal, $_POST['status'], current_user()['id']]);
            $pid = $pdo->lastInsertId();
        }
        foreach ($_POST['item_id'] as $idx => $itemId) {
            if (!$itemId) continue;
            $qty = (float)$_POST['qty'][$idx];
            $price = $money($_POST['price'][$idx]);
            $batchNo = trim($_POST['batch_no'][$idx] ?? '');
            $expiry = $_POST['expiry'][$idx] ?? null;
            $subtotal = $qty * $price;
            $stmt = $pdo->prepare("INSERT INTO purchase_items(purchase_id,item_id,qty,price,subtotal,batch_no,expiry) VALUES(?,?,?,?,?,?,?)");
            $stmt->execute([$pid, $itemId, $qty, $price, $subtotal, $batchNo ?: null, $expiry ?: null]);
            if ($_POST['status'] === 'posted') {
                // update batch
                $batchId = null;
                if ($batchNo) {
                    $exists = $pdo->prepare("SELECT id FROM batches WHERE item_id=? AND batch_no=?");
                    $exists->execute([$itemId, $batchNo]);
                    $batchId = $exists->fetchColumn();
                    if ($batchId) {
                        $pdo->prepare("UPDATE batches SET stock = stock + ?, expiry = COALESCE(?, expiry) WHERE id=?")->execute([$qty, $expiry, $batchId]);
                    } else {
                        $pdo->prepare("INSERT INTO batches(item_id,batch_no,expiry,stock) VALUES(?,?,?,?)")
                            ->execute([$itemId,$batchNo,$expiry,$qty]);
                        $batchId = $pdo->lastInsertId();
                    }
                }
                $pdo->prepare("UPDATE items SET stock = stock + ?, buy_price=? WHERE id=?")->execute([$qty, $price, $itemId]);
                $pdo->prepare("INSERT INTO stock_moves(item_id, ref_type, ref_id, date, qty_in, qty_out, note, created_by, batch_id, expiry) VALUES(?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$itemId, 'purchase', $pid, $_POST['date'], $qty, 0, 'Pembelian', current_user()['id'], $batchId, $expiry]);
            }
        }
        if ($_POST['status'] === 'posted') {
            $pdo->prepare("INSERT INTO cashbooks(date,type,amount,note,ref_type,ref_id,created_by) VALUES(?,?,?,?,?,?,?)")
                ->execute([$_POST['date'],'out',$totalVal,'Pembelian '.$purchaseNo,'purchase',$pid,current_user()['id']]);
        }
        $pdo->commit();
        redirect('/transaksi/pembelian_index.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        log_error($e->getMessage());
        die('Gagal simpan');
    }
}
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Pembelian</h4>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($purchase['id']); ?>">
    <div class="row g-2 mb-2">
        <div class="col-md-3">
            <label>Supplier</label>
            <select class="form-select" name="supplier_id">
                <option value="">-</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id']; ?>" <?= $purchase['supplier_id']==$s['id']?'selected':''; ?>><?= htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Tanggal</label>
            <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($purchase['date']); ?>">
        </div>
        <div class="col-md-2">
            <label>Status</label>
            <select class="form-select" name="status">
                <option value="draft" <?= $purchase['status']=='draft'?'selected':''; ?>>Draft</option>
                <option value="posted" <?= $purchase['status']=='posted'?'selected':''; ?>>Posted</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>No Pembelian</label>
            <input class="form-control" name="purchase_no" readonly value="<?= htmlspecialchars($purchase['purchase_no']); ?>">
        </div>
    </div>
    <div class="mb-2">
        <label class="small">Scan / Cari Barang (kode/barcode/nama)</label>
        <div class="position-relative">
            <input class="form-control" id="searchItem" placeholder="Ketik lalu Enter" autocomplete="off">
            <div class="list-group position-absolute w-100" style="z-index:5;" id="searchSuggest" hidden></div>
        </div>
    </div>
    <table class="table table-bordered" id="itemTable">
        <thead><tr><th>Barang</th><th>Batch</th><th>Kadaluarsa</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php $rowsCount = max(1, count($details)); for ($i=0;$i<$rowsCount;$i++): $d=$details[$i]??['item_id'=>'','qty'=>1,'price'=>0]; ?>
        <tr>
            <td>
                <select class="form-select item-select" name="item_id[]" onchange="fillPrice(this)">
                    <option value="">-</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id']; ?>"
                            data-price="<?= $it['buy_price']; ?>"
                            data-last="<?= $it['last_price'] ?? $it['buy_price']; ?>"
                            <?= $d['item_id']==$it['id']?'selected':''; ?>>
                            <?= $it['code'].' - '.$it['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input class="form-control" name="batch_no[]" value="<?= htmlspecialchars($d['batch_no'] ?? ''); ?>"></td>
            <td><input type="date" class="form-control" name="expiry[]" value="<?= htmlspecialchars($d['expiry'] ?? ''); ?>"></td>
            <td><input type="number" step="0.01" class="form-control qty" name="qty[]" value="<?= $d['qty'] ?: 1; ?>"></td>
            <td><input type="text" class="form-control price rupiah-input" name="price[]" value="<?= $d['price'] ?: $items[0]['buy_price'] ?? 0; ?>"></td>
            <td class="subtotal">0</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="delRow(this)">X</button></td>
        </tr>
        <?php endfor; ?>
        </tbody>
    </table>
    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addRow()">Tambah Baris</button>
    <div class="mb-2">
        <label>Total</label>
        <input class="form-control rupiah-input" id="total" name="total" readonly value="<?= $purchase['total']; ?>">
    </div>
    <button class="btn btn-primary">Simpan</button>
</form>
<script>
const moneyParse = (val) => {
    if (typeof parseMoneyIndo === 'function') return parseMoneyIndo(val);
    let s = (val||'').toString().replace(/\s+/g,'').replace(/,/g,'.');
    const parts = s.split('.');
    if (parts.length===1) return parseInt(parts[0]||0,10)||0;
    if (parts.length===2 && parts[1].length===3) return parseInt(parts[0]+parts[1],10)||0;
    const n = parseFloat(parts[0]+'.'+(parts[1]||0));
    return isNaN(n)?0:Math.round(n);
};
const itemsData = <?= json_encode($items); ?>;
function addRow(){
    const tpl = document.querySelector('#itemTable tbody tr');
    if (!tpl) return;
    const row = tpl.cloneNode(true);
    row.querySelectorAll('select').forEach(el=>el.selectedIndex=0);
    row.querySelectorAll('input.qty').forEach(el=>el.value=1);
    row.querySelectorAll('input.price').forEach(el=>{
        el.value='';
        if (el.classList.contains('rupiah-input')) el.dispatchEvent(new Event('input'));
    });
    row.querySelectorAll('.subtotal').forEach(el=>el.innerText='0');
    document.querySelector('#itemTable tbody').appendChild(row);
    bindRow(row);
}
function delRow(btn){
    const tr = btn.closest('tr');
    if (document.querySelectorAll('#itemTable tr').length>2) tr.remove();
    calc();
}
function fillPrice(sel){
    const opt = sel.selectedOptions[0];
    if (!opt) return;
    const last = opt.dataset.last || opt.dataset.price || 0;
    const priceInput = sel.closest('tr').querySelector('.price');
    if (priceInput) {
        priceInput.value = last;
        if (priceInput.classList.contains('rupiah-input')) priceInput.dispatchEvent(new Event('input'));
    }
    calc();
}
function calc(){
    let total=0;
    document.querySelectorAll('#itemTable tbody tr').forEach((tr)=>{
        const qty = parseFloat(tr.querySelector('.qty').value||0);
        const price = moneyParse(tr.querySelector('.price').value||'');
        const sub = qty*price;
        tr.querySelector('.subtotal').innerText=sub.toFixed(2);
        total += sub;
    });
    const totalInput = document.getElementById('total');
    totalInput.value = total.toFixed(0);
    if (totalInput.classList.contains('rupiah-input')) totalInput.dispatchEvent(new Event('input'));
}
function bindRow(row){
    row.querySelectorAll('.qty,.price').forEach(el=>el.addEventListener('input',calc));
    const sel = row.querySelector('.item-select');
    if (sel) sel.addEventListener('change', ()=>fillPrice(sel));
}
document.querySelectorAll('#itemTable tbody tr').forEach(bindRow);
calc();

// Search ala POS
const inputSearch = document.getElementById('searchItem');
const suggestBox = document.getElementById('searchSuggest');
inputSearch.addEventListener('input', ()=>{
    const term = (inputSearch.value||'').toLowerCase();
    if (!term) { suggestBox.innerHTML=''; suggestBox.hidden=true; return; }
    const found = itemsData.filter(i => (i.code||'').toLowerCase().includes(term) || (i.barcode||'').toLowerCase().includes(term) || (i.name||'').toLowerCase().includes(term)).slice(0,8);
    suggestBox.innerHTML = '';
    found.forEach(f=>{
        const btn = document.createElement('button');
        btn.type='button';
        btn.className='list-group-item list-group-item-action bg-dark text-white';
        btn.innerHTML = `<div class="fw-semibold">${f.name}</div><div class="small text-muted">Kode: ${f.code} | Harga: Rp ${Number(f.last_price||f.buy_price||0).toLocaleString('id-ID')}</div>`;
        btn.addEventListener('click', ()=>{ addItemToRow(f); inputSearch.value=''; suggestBox.hidden=true; });
        suggestBox.appendChild(btn);
    });
    suggestBox.hidden = found.length===0;
});
inputSearch.addEventListener('keydown',(e)=>{
    if (e.key==='Enter') {
        e.preventDefault();
        const term=(inputSearch.value||'').toLowerCase();
        const f=itemsData.find(i=>i.code.toLowerCase()===term || (i.barcode||'').toLowerCase()===term) || itemsData.find(i=>i.name.toLowerCase().includes(term));
        if (f){ addItemToRow(f); inputSearch.value=''; suggestBox.hidden=true; }
    }
});
document.addEventListener('click',(e)=>{ if (!suggestBox.contains(e.target) && e.target!==inputSearch) suggestBox.hidden=true; });

function addItemToRow(item){
    let targetRow = null;
    document.querySelectorAll('#itemTable tbody tr').forEach(tr=>{
        const sel = tr.querySelector('select.item-select');
        if (sel && !sel.value && !targetRow) targetRow = tr;
    });
    if (!targetRow) { addRow(); targetRow = document.querySelector('#itemTable tbody tr:last-child'); }
    const sel = targetRow.querySelector('select.item-select');
    sel.value = item.id;
    const priceInput = targetRow.querySelector('.price');
    priceInput.value = item.last_price || item.buy_price || 0;
    if (priceInput.classList.contains('rupiah-input')) priceInput.dispatchEvent(new Event('input'));
    targetRow.querySelector('.qty').value = 1;
    calc();
}
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
