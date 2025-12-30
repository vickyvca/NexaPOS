<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
$pdo = getPDO();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$items = $pdo->query("SELECT id,code,barcode,name,sell_price,sell_price_lv2,sell_price_lv3,stock FROM items WHERE is_active=1 ORDER BY name LIMIT 200")->fetchAll();
$batches = $pdo->query("SELECT b.id,b.item_id,b.batch_no,b.expiry,b.stock FROM batches b JOIN items i ON i.id=b.item_id WHERE b.stock>0 ORDER BY b.expiry IS NULL, b.expiry, b.id")->fetchAll();
$cart = $_SESSION['cart'];
$totalInit = 0;
foreach ($cart as $c) { $totalInit += ($c['price'] * $c['qty']) - ($c['discount'] ?? 0); }
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div x-data="posApp()" x-init="init()" x-cloak>
<div class="row g-3">
    <div class="col-md-5">
        <input type="hidden" id="csrf" value="<?= csrf_token(); ?>">
        <div class="row g-2 mb-2">
            <div class="col-8">
                <label class="small">Scan / Cari Barang</label>
                <input class="form-control" x-model.trim="scanCode" @input="searchSuggest()" @keydown.enter.prevent="addByCode()" autocomplete="off">
                <div class="position-relative">
                    <div class="list-group position-absolute w-100" style="z-index:5;" x-show="suggestions.length>0" @click.outside="suggestions=[]">
                        <template x-for="s in suggestions" :key="s.id">
                            <button type="button" class="list-group-item list-group-item-action bg-dark text-white" @click="addItem(s); suggestions=[]">
                                <div class="fw-semibold" x-text="s.name"></div>
                                <div class="small text-muted">Kode: <span x-text="s.code"></span> | Harga: <span x-text="formatRupiah(pickPriceLocal(s))"></span></div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <label class="small">Level Harga</label>
                <select class="form-select" x-model.number="priceLevel" @change="syncCardPrices()">
                    <option value="1">Level 1</option>
                    <option value="2">Level 2</option>
                    <option value="3">Level 3</option>
                </select>
            </div>
        </div>
        <div class="card card-pos mb-3">
            <div class="card-body p-2">
                <template x-if="cart.length === 0">
                    <div class="text-center text-muted py-3">Cart kosong</div>
                </template>
                        <div class="d-flex flex-column gap-2">
                            <template x-for="(item, idx) in cart" :key="item.id + '-' + idx">
                                <div class="d-flex align-items-center justify-content-between p-2 rounded cart-item" style="background:#0d1628;">
                                    <div class="flex-fill me-2">
                                        <div class="fw-semibold" x-text="item.name"></div>
                                        <div class="small text-muted">Lvl 
                                            <select class="form-select form-select-sm d-inline-block" style="width:70px" x-model.number="item.level" @change="updatePrice(idx)">
                                                <option value="1">1</option><option value="2">2</option><option value="3">3</option>
                                            </select>
                                            | Harga: <span style="color:#7dd3fc;" x-text="formatRupiah(item.price)"></span>
                                        </div>
                                <div class="small text-muted">Batch: <span x-text="item.batch_no || '-'"></span> | Exp: <span x-text="item.expiry || '-'"></span></div>
                                <div class="small text-muted">Disc: 
                                    <input type="number" class="form-control form-control-sm d-inline-block text-end" style="width:90px" x-model.number="item.discount" @change="sanitizeDiscount(idx)">
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-secondary" @click="incQty(idx,-1)">-</button>
                                <div class="fw-bold" x-text="item.qty"></div>
                                <button class="btn btn-sm btn-secondary" @click="incQty(idx,1)">+</button>
                            </div>
                            <div class="text-end ms-3">
                                <div class="fw-bold" x-text="formatRupiah(lineTotal(item))"></div>
                                <button class="btn btn-sm btn-danger mt-1" @click="removeItem(idx)"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <button class="btn btn-outline-light btn-sm" @click="clearCart()"><i class="bi bi-trash"></i> Kosongkan</button>
            <strong>Total: <span x-text="formatRupiah(total)"></span></strong>
        </div>
        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#checkoutModal" :disabled="cart.length===0"><i class="bi bi-wallet2"></i> Bayar</button>
    </div>
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>Shortcut Barang</div>
            <small class="text-muted">Klik untuk tambah cepat</small>
        </div>
        <div class="row row-cols-2 row-cols-md-3 g-2">
            <?php foreach ($items as $it): ?>
            <div class="col">
                <div class="card h-100 shadow-sm card-pos" 
                     data-id="<?= $it['id']; ?>" 
                     data-code="<?= htmlspecialchars($it['code']); ?>" 
                     data-name="<?= htmlspecialchars($it['name']); ?>" 
                     data-l1="<?= $it['sell_price']; ?>" 
                     data-l2="<?= $it['sell_price_lv2']; ?>" 
                     data-l3="<?= $it['sell_price_lv3']; ?>">
                    <div class="card-body p-2 d-flex flex-column">
                        <div class="fw-semibold h6 mb-1" style="min-height:40px;"><?= htmlspecialchars($it['name']); ?></div>
                        <div class="display-6 fs-4 price-display"
                             data-l1="<?= $it['sell_price']; ?>"
                             data-l2="<?= $it['sell_price_lv2']; ?>"
                             data-l3="<?= $it['sell_price_lv3']; ?>">
                             <?= format_rupiah($it['sell_price']); ?>
                        </div>
                        <div class="stock-label">Stok: <?= $it['stock']; ?> | Level: <span class="price-level-label">1</span></div>
                    </div>
                    <div class="card-footer p-2 text-end">
                        <button class="btn btn-sm btn-outline-light w-100" @click="addItem({id:<?= $it['id']; ?>, code:'<?= htmlspecialchars($it['code']); ?>', name:'<?= htmlspecialchars($it['name']); ?>', l1:<?= $it['sell_price']; ?>, l2:<?= $it['sell_price_lv2']; ?>, l3:<?= $it['sell_price_lv3']; ?>})"><i class="bi bi-plus-circle"></i> Tambah</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div><!-- end row -->

<div class="modal fade" id="checkoutModal">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="checkout.php" @submit.prevent="beforeSubmit($event)">
            <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
            <input type="hidden" name="cart_json" x-ref="cartField" x-model="cartJson" x-bind:value="cartJson">
            <div class="modal-header"><h5 class="modal-title">Checkout</h5></div>
            <div class="modal-body">
                <div class="checkout-summary">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Subtotal</span><span class="fw-semibold" x-text="formatRupiah(total)"></span>
                    </div>
                        <div class="d-flex justify-content-between">
                            <span>Diskon</span>
                            <span>
                                <input type="text" name="discount_total" class="form-control form-control-sm text-end" x-on:input="handleMoneyInput('discountTotal',$event)" style="width:120px; display:inline-block;">
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Pajak</span>
                            <span>
                                <input type="text" name="tax" class="form-control form-control-sm text-end" x-on:input="handleMoneyInput('tax',$event)" style="width:120px; display:inline-block;">
                            </span>
                        </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Grand Total</span><span x-text="formatRupiah(grandTotal)"></span>
                    </div>
                    <div class="mt-2">
                        <label class="small">Metode Bayar</label>
                        <select class="form-select form-select-sm" name="payment_method" x-model="paymentMethod">
                            <option value="cash">cash</option><option value="transfer">transfer</option><option value="QRIS">QRIS</option>
                        </select>
                    </div>
                        <div class="mt-2">
                            <label class="small">Bayar</label>
                            <input type="text" class="form-control text-end" name="cash_paid" x-on:input="handleMoneyInput('cashPaid',$event)">
                        </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Kembali</span><span class="fw-semibold" x-text="formatRupiah(changeAmount)"></span>
                    </div>
                    <div class="mt-2"><label class="small">Pelanggan (opsional)</label><input class="form-control form-control-sm" name="customer_name" x-model="customerName"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" :disabled="cart.length===0">Proses</button>
            </div>
        </form>
    </div>
</div>

<script>
function posApp() {
    return {
        priceLevel: 1,
        scanCode: '',
        cart: [],
        itemsData: <?= json_encode(array_map(function($i){ return ['id'=>$i['id'],'code'=>$i['code'],'barcode'=>$i['barcode'],'name'=>$i['name'],'l1'=>$i['sell_price'],'l2'=>$i['sell_price_lv2'],'l3'=>$i['sell_price_lv3']]; }, $items)); ?>,
        batches: <?= json_encode($batches); ?>,
        discountTotal: 0,
        tax: 0,
        cashPaid: 0,
        paymentMethod: 'cash',
        customerName: '',
        cartJson: '',
        total: 0,
        grandTotal: 0,
        changeAmount: 0,
        suggestions: [],
        init() {
            const initial = document.querySelector('[name="cart_json"]')?.value || '[]';
            try { this.cart = JSON.parse(initial); } catch(e){ this.cart = []; }
            this.cart = this.cart.map(c => ({...c, id: c.id ?? c.item_id ?? null, l1: c.price ?? c.l1 ?? 0, l2: c.l2 ?? c.sell_price_lv2 ?? 0, l3: c.l3 ?? c.sell_price_lv3 ?? 0, discount: c.discount ?? 0, qty: c.qty ?? 1, level: c.level ?? 1, batch_id: c.batch_id ?? null, batch_no: c.batch_no ?? null, expiry: c.expiry ?? null}));
            this.recalc();
            this.syncCardPrices();
        },
        addByCode() {
            if (!this.scanCode) return;
            const term = this.scanCode.toLowerCase();
            const exact = this.itemsData.find(i => i.code.toLowerCase() === term || (i.barcode||'').toLowerCase() === term);
            const fuzzy = this.itemsData.find(i => i.code.toLowerCase().includes(term) || i.name.toLowerCase().includes(term));
            const target = exact || fuzzy;
            if (target) this.addItem(target);
            this.scanCode = '';
            this.suggestions = [];
        },
        searchSuggest(){
            const term = (this.scanCode||'').toLowerCase();
            if (!term) { this.suggestions=[]; return; }
            this.suggestions = this.itemsData.filter(i => i.code.toLowerCase().includes(term) || (i.barcode||'').toLowerCase().includes(term) || i.name.toLowerCase().includes(term)).slice(0,8);
        },
        addItem(item) {
            const batch = this.pickBatch(item.id);
            if (!batch) { alert('Stok/batch habis atau kadaluarsa'); return; }
            const price = this.pickPrice(item);
            const existing = this.cart.find(i => i.id === item.id && i.batch_id === batch.id);
            if (existing) {
                const used = this.usedBatchQty(batch.id);
                if (used + 1 > batch.stock) { alert('Stok batch ini habis'); return; }
                existing.qty += 1;
                existing.price = price;
                existing.level = this.priceLevel;
            } else {
                this.cart.push({id:item.id, name:item.name, price, qty:1, discount:0, level:this.priceLevel, l1:item.l1, l2:item.l2, l3:item.l3, batch_id: batch.id, batch_no: batch.batch_no, expiry: batch.expiry});
            }
            this.recalc();
        },
        removeItem(idx) { this.cart.splice(idx,1); this.recalc(); },
        clearCart() { this.cart = []; this.recalc(); },
        sanitizeQty(idx){ if (this.cart[idx].qty < 1) this.cart[idx].qty = 1; this.recalc(); },
        sanitizeDiscount(idx){ if (this.cart[idx].discount < 0) this.cart[idx].discount = 0; this.recalc(); },
        updatePrice(idx){ const it=this.cart[idx]; it.price=this.pickPrice(it); this.recalc(); },
        incQty(idx,delta){
            const it=this.cart[idx]; if(!it) return;
            if (delta>0 && it.batch_id){
                const batch = this.batches.find(b=>b.id===it.batch_id);
                if (batch){
                    const used = this.usedBatchQty(it.batch_id);
                    if (used + delta > batch.stock) { alert('Stok batch tidak cukup'); return; }
                }
            }
            it.qty += delta;
            if(it.qty<=0){ this.cart.splice(idx,1);}
            this.recalc();
        },
        lineTotal(item){ return (item.price * item.qty) - (item.discount||0); },
        usedBatchQty(batchId){
            let total = 0;
            this.cart.forEach(c=>{ if (c.batch_id === batchId) total += Number(c.qty||0); });
            return total;
        },
        pickBatch(itemId){
            const batches = this.batches.filter(b=>b.item_id==itemId).sort((a,b)=>{
                if (!a.expiry && b.expiry) return 1;
                if (a.expiry && !b.expiry) return -1;
                if (a.expiry && b.expiry && a.expiry !== b.expiry) return a.expiry < b.expiry ? -1 : 1;
                return a.id - b.id;
            });
            for (const b of batches){
                if (b.expiry && b.expiry < this.today()) continue;
                const used = this.usedBatchQty(b.id);
                const avail = (parseFloat(b.stock)||0) - used;
                if (avail > 0) return {...b, available: avail};
            }
            return null;
        },
        today(){ return new Date().toISOString().slice(0,10); },
        pickPrice(item){
            if (this.priceLevel===2) return item.l2 ?? item.sell_price_lv2 ?? item.price;
            if (this.priceLevel===3) return item.l3 ?? item.sell_price_lv3 ?? item.price;
            return item.l1 ?? item.sell_price ?? item.price;
        },
        recalc(){
            this.total = this.cart.reduce((sum,i)=>sum + this.lineTotal(i), 0);
            this.calcPayment();
            this.cartJson = JSON.stringify(this.cart);
            if (this.$refs.cartField) this.$refs.cartField.value = this.cartJson;
            console.log('recalc cart', this.cartJson);
        },
        calcPayment(){
            let grand = this.total - (this.discountTotal||0) + (this.tax||0);
            if (grand < 0) grand = 0;
            this.grandTotal = grand;
            const pay = this.cashPaid||0;
            this.changeAmount = pay > grand ? pay - grand : 0;
        },
        formatRupiah(n){ return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(n||0); },
        formatNumber(n){ return (n||0).toLocaleString('id-ID'); },
        parseMoney(val){
            if (typeof parseMoneyIndo === 'function') return parseMoneyIndo(val);
            let s = (val||'').toString().replace(/\s+/g,'').replace(/,/g,'.');
            const parts = s.split('.');
            if (parts.length===1) return parseInt(parts[0]||0,10)||0;
            if (parts.length===2 && parts[1].length===3) return parseInt(parts[0]+parts[1],10)||0;
            const n = parseFloat(parts[0]+'.'+(parts[1]||0));
            return isNaN(n)?0:Math.round(n);
        },
        handleMoneyInput(field, ev){
            const num = this.parseMoney(ev.target.value);
            this[field] = num;
            ev.target.value = this.formatNumber(num);
            this.calcPayment();
        },
        syncCardPrices(){
            const lvl = this.priceLevel;
            document.querySelectorAll('.price-display').forEach(el=>{
                const price = lvl===2 ? el.dataset.l2 : (lvl===3 ? el.dataset.l3 : el.dataset.l1);
                el.textContent = this.formatRupiah(parseFloat(price||0));
                const label = el.parentElement.querySelector('.price-level-label');
                if (label) label.textContent = lvl;
            });
            this.cart.forEach(i=>{ i.level = lvl; i.price = this.pickPrice(i); });
            this.recalc();
        },
        async beforeSubmit(e){
            if (this.cart.length === 0) { alert('Cart kosong'); return; }
            this.recalc();
            // sinkron ke sesi supaya checkout fallback aman
            try {
                const formData = new URLSearchParams();
                formData.append('action','sync');
                formData.append('csrf', document.getElementById('csrf').value);
                formData.append('cart_json', this.cartJson);
                const res = await fetch('cart_api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: formData.toString() });
                const out = await res.json();
                console.log('sync response', out);
            } catch(err){ console.error('sync error', err); }
            console.log('submit cart', this.cartJson);
            e.target.submit();
        }
    }
}
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
