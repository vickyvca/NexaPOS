<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lamp_control.php';
check_login();

$stmt = $pdo->query("SELECT t.*, s.id AS session_id, s.start_time, s.customer_name, s.customer_phone, s.package_id, p.duration_minutes AS pkg_duration, p.name AS pkg_name,
    (SELECT end_time FROM maintenance_logs ml WHERE ml.table_id = t.id ORDER BY ml.id DESC LIMIT 1) AS maintenance_end
    FROM billiard_tables t
    LEFT JOIN sessions s ON s.table_id = t.id AND s.status = 'running'
    LEFT JOIN packages p ON s.package_id = p.id
    ORDER BY t.id ASC");
$tables = $stmt->fetchAll();

// auto end maintenance if overdue
foreach ($tables as &$table) {
    if ($table['status'] === 'maintenance' && $table['maintenance_end']) {
        if (strtotime($table['maintenance_end']) <= time()) {
            $pdo->prepare("UPDATE maintenance_logs SET end_time = NOW() WHERE table_id = ? AND end_time = ?")->execute([$table['id'], $table['maintenance_end']]);
            $pdo->prepare("UPDATE billiard_tables SET status='idle' WHERE id = ?")->execute([$table['id']]);
            if ($table['controller_ip'] && $table['relay_channel']) {
                call_lamp($table['controller_ip'], $table['relay_channel'], 'off');
            }
            $table['status'] = 'idle';
            $table['maintenance_end'] = null;
        }
    }
}
unset($table);

$stats = [
    'grand' => 0,
    'pos' => 0,
    'billiard' => 0,
    'running' => 0,
    'total_tables' => count($tables)
];
$activeShift = get_active_shift($pdo, $_SESSION['user']['id'] ?? 0);
$todayStmt = $pdo->query("SELECT COALESCE(SUM(grand_total),0) AS grand, COALESCE(SUM(subtotal),0) AS pos FROM orders WHERE DATE(order_time) = CURDATE() AND is_paid = 1");
$row = $todayStmt->fetch();
$stats['grand'] = (int)$row['grand'];
$stats['pos'] = (int)$row['pos'];
$stats['billiard'] = $stats['grand'] - $stats['pos'];
$stats['running'] = count(array_filter($tables, function($t){ return $t['status'] === 'running'; }));
$payBreak = ['cash'=>0,'transfer'=>0,'qris'=>0,'other'=>0];
$payStmt = $pdo->query("SELECT payment_method, SUM(grand_total) AS amt FROM orders WHERE DATE(order_time)=CURDATE() AND is_paid=1 GROUP BY payment_method");
foreach ($payStmt as $p) {
    $m = strtolower($p['payment_method'] ?? 'other');
    if (!isset($payBreak[$m])) $m = 'other';
    $payBreak[$m] += (int)$p['amt'];
}
// extra charge hari ini
$extraStmt = $pdo->query("SELECT COALESCE(SUM(extra_charge_amount),0) AS extra_sum FROM orders WHERE DATE(order_time)=CURDATE() AND is_paid=1");
$extraRow = $extraStmt->fetch();
$extraToday = (int)($extraRow['extra_sum'] ?? 0);
// log maintenance terbaru
$maintRecent = $pdo->query("SELECT ml.*, t.name AS table_name, u.username FROM maintenance_logs ml JOIN billiard_tables t ON ml.table_id = t.id LEFT JOIN users u ON ml.operator_id = u.id ORDER BY ml.start_time DESC LIMIT 3")->fetchAll();
?>
<?php $isAdmin = !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Dashboard Meja</h3>
        <small class="text-secondary">Kontrol sesi billiard & akses cepat POS</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <button class="btn btn-success btn-sm" id="btnOnAll">Nyalakan Semua</button>
        <button class="btn btn-danger btn-sm" id="btnOffAll">Matikan Semua</button>
        <a class="btn btn-outline-light btn-sm" href="/billiard_pos/tables/list.php">Kelola Meja</a>
        <a class="btn btn-warning btn-sm" href="/billiard_pos/reports/shift_control.php">Rekap Kas / Shift</a>
        <button class="btn btn-info btn-sm" id="btnMoveTable">Pindah Meja</button>
    </div>
</div>
<div class="row g-2 mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text">Cari Meja</span>
            <input type="text" id="tableSearch" class="form-control" placeholder="ketik nama meja...">
        </div>
    </div>
    <div class="col-md-6">
        <?php if ($maintRecent): ?>
            <div class="card bg-secondary text-light">
                <div class="card-body py-2">
                    <div class="fw-bold small mb-1">Maintenance Terbaru</div>
                    <?php foreach ($maintRecent as $m): ?>
                        <div class="small text-muted">• <?php echo htmlspecialchars($m['table_name']); ?> oleh <?php echo htmlspecialchars($m['username'] ?? ''); ?> (<?php echo format_datetime($m['start_time']); ?>)</div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="small text-info mb-2" id="lampStatus"></div>
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft accent"><span class="me-1">&#128176;</span>Pendapatan</div>
                </div>
                <div class="fs-4 fw-bold mt-2"><?php echo format_rupiah($stats['grand']); ?></div>
                <div class="text-muted small">Hari ini</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft info"><span class="me-1">&#127921;</span>Billing</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($stats['billiard']); ?></div>
                <div class="text-muted small">Total billing billiard</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft warn"><span class="me-1">&#128722;</span>POS</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($stats['pos']); ?></div>
                <div class="text-muted small">Subtotal POS</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft accent"><span class="me-1">&#128204;</span>Meja Terisi</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo $stats['running']; ?> / <?php echo $stats['total_tables']; ?></div>
                <div class="text-muted small">Running / Total</div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft accent"><span class="me-1">&#128181;</span>Cash</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($payBreak['cash']); ?></div>
                <div class="text-muted small">Pembayaran tunai</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft info"><span class="me-1">&#127974;</span>Transfer</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($payBreak['transfer']); ?></div>
                <div class="text-muted small">Pembayaran bank</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft warn"><span class="me-1">&#128241;</span>QRIS</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($payBreak['qris']); ?></div>
                <div class="text-muted small">Pembayaran QR</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-light h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="badge-soft accent"><span class="me-1">&#10133;</span>Extra</div>
                </div>
                <div class="fs-5 fw-bold mt-2"><?php echo format_rupiah($extraToday); ?></div>
                <div class="text-muted small">Charge tambahan</div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3">
    <?php foreach ($tables as $table): ?>
        <?php
            $cat = $table['category'] ?? (stripos($table['name'], 'vip') !== false ? 'vip' : 'regular');
            $catClass = $cat === 'vip' ? 'vip' : 'regular';
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="p-3 table-card <?php echo $table['status']; ?> <?php echo $catClass; ?>">
                <div class="status-ribbon"><?php echo $table['status']; ?></div>
                <div class="table-tag"><?php echo strtoupper($cat); ?></div>
                    <div class="table-visual">
                        <div class="d-flex justify-content-between p-2">
                            <div class="big-text"><?php echo htmlspecialchars($table['name']); ?></div>
                            <div class="text-end small">
                                <div>
                                <?php if ($table['status']==='running'): ?>
                                    <span class="badge bg-success status-badge-pill">Running</span>
                                <?php elseif ($table['status']==='maintenance'): ?>
                                    <span class="badge bg-info text-dark status-badge-pill">Maintenance</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary status-badge-pill">Idle</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($table['customer_name']): ?>
                                <div class="text-warning"><?php echo htmlspecialchars($table['customer_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="felt-balls">
                        <div class="felt-ball ball-yellow"></div>
                        <div class="felt-ball ball-red"></div>
                        <div class="felt-ball ball-blue"></div>
                        <div class="felt-ball ball-white"></div>
                    </div>
                    <?php if ($table['status'] === 'running' && $table['start_time']): ?>
                        <div class="duration-pill text-center" style="position:absolute;left:50%;bottom:12px;transform:translateX(-50%);background:rgba(0,0,0,0.55);padding:6px 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.2);font-weight:800;letter-spacing:0.3px;z-index:2;font-size:1.2rem;"
                             data-start-time="<?php echo htmlspecialchars($table['start_time']); ?>"
                             data-session-id="<?php echo $table['session_id']; ?>"
                             data-pkg-duration="<?php echo (int)$table['pkg_duration']; ?>"
                             data-table-name="<?php echo htmlspecialchars($table['name']); ?>">
                             00:00:00
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($table['status'] === 'maintenance'): ?>
                    <div class="mt-2">
                        <div class="d-flex align-items-center gap-2 text-info">
                            <span class="pulse-dot"></span>
                            <span class="small">Maintenance</span>
                        </div>
                        <div class="display-6" data-maintenance-end="<?php echo htmlspecialchars($table['maintenance_end']); ?>">00:00</div>
                        <div class="text-muted small">Lampu akan mati otomatis.</div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-outline-light w-100 btn-maintenance" data-table="<?php echo $table['id']; ?>" data-maint-action="off">Matikan Maintenance</button>
                    </div>
                <?php elseif ($table['status'] === 'running' && $table['start_time']): ?>
                    <div class="mt-2 text-muted small">Customer: <?php echo htmlspecialchars($table['customer_name']); ?> (<?php echo htmlspecialchars($table['customer_phone']); ?>)</div>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-danger w-100 btn-icon btn-stop-session" data-table="<?php echo $table['id']; ?>">&#9632; Stop</button>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <a class="btn btn-warning w-100 btn-icon" href="/billiard_pos/pos/pos.php?table_id=<?php echo $table['id']; ?>">🛒 POS</a>
                        <a class="btn btn-outline-light w-100 btn-icon" href="/billiard_pos/pos/checkout.php?table_id=<?php echo $table['id']; ?>">🧾 Checkout</a>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-info w-100 btn-maintenance" data-table="<?php echo $table['id']; ?>" data-maint-action="on">Maintenance (lampu)</button>
                    </div>
                <?php else: ?>
                    <div class="mt-3 text-secondary">Belum ada sesi berjalan.</div>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-success w-100 btn-icon btn-start-session" data-table="<?php echo $table['id']; ?>">▶ Start</button>
                        <a class="btn btn-outline-warning w-100 btn-icon" href="/billiard_pos/pos/pos.php?table_id=<?php echo $table['id']; ?>">🛒 POS</a>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-info w-100 btn-maintenance" data-table="<?php echo $table['id']; ?>" data-maint-action="on">Maintenance (lampu)</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<!-- Modal Pindah Meja -->
<div class="modal fade" id="moveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header"><h5 class="modal-title">Pindah Meja</h5></div>
            <div class="modal-body">
                <form id="moveForm">
                    <div class="mb-3">
                        <label class="form-label">Dari Meja (sedang berjalan)</label>
                        <select class="form-select" name="from_table_id" id="fromTableSelect" required>
                            <option value="">Pilih meja</option>
                            <?php foreach ($tables as $t): if ($t['status'] !== 'running') continue; ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ke Meja (idle)</label>
                        <select class="form-select" name="to_table_id" id="toTableSelect" required>
                            <option value="">Pilih meja</option>
                            <?php foreach ($tables as $t): if ($t['status'] !== 'idle') continue; ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="small text-muted">Billing meja asal dihitung parsial, lalu lanjut dengan tarif meja baru.</div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="submitMove">Pindahkan</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Lamp Control -->
<div class="modal fade" id="lampModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-secondary text-light">
      <div class="modal-header">
        <h5 class="modal-title">Kontrol Lampu Semua Meja</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Durasi menyala (menit, opsional)</label>
        <input type="number" min="1" class="form-control" id="lampDuration" value="10">
        <small class="text-muted">Jika dikosongkan, akan dianggap 10 menit.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="lampOnAllModal">Nyalakan Semua</button>
        <button type="button" class="btn btn-danger" id="lampOffAllModal">Matikan Semua</button>
      </div>
    </div>
  </div>
</div>
<script>
const lampStatus = document.getElementById('lampStatus');
const MAINT_PASS_REQUIRED = <?php echo isset($company) && !empty($company['maintenance_password']) ? 'true' : 'false'; ?>;
function callAll(action){
    let pass = '';
    if (MAINT_PASS_REQUIRED) {
        pass = prompt('Password kontrol lampu (wajib):','') || '';
    }
    const dur = document.getElementById('lampDuration') ? document.getElementById('lampDuration').value : 10;
    fetch('/billiard_pos/api/lamp_all.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action='+action+'&duration='+encodeURIComponent(dur)+'&pass='+encodeURIComponent(pass)
    }).then(r=>r.json()).then(j=>{
        if (lampStatus) lampStatus.textContent = (j.message||'') + ' @'+(j.time||'');
    }).catch(()=>{ if (lampStatus) lampStatus.textContent='Gagal memanggil API'; });
}
document.getElementById('btnOnAll')?.addEventListener('click', ()=> {
    const modal = new bootstrap.Modal(document.getElementById('lampModal'));
    modal.show();
    document.getElementById('lampOnAllModal').onclick = ()=>{ modal.hide(); callAll('on_all'); };
    document.getElementById('lampOffAllModal').onclick = ()=>{ modal.hide(); callAll('off_all'); };
});
document.getElementById('btnOffAll')?.addEventListener('click', ()=> {
    const modal = new bootstrap.Modal(document.getElementById('lampModal'));
    modal.show();
    document.getElementById('lampOnAllModal').onclick = ()=>{ modal.hide(); callAll('on_all'); };
    document.getElementById('lampOffAllModal').onclick = ()=>{ modal.hide(); callAll('off_all'); };
});

// Lamp per meja (password jika di-set di company.json -> maintenance_password)
// Pindah meja
document.getElementById('btnMoveTable')?.addEventListener('click', ()=> {
    const moveModal = new bootstrap.Modal(document.getElementById('moveModal'));
    moveModal.show();
});
document.getElementById('submitMove')?.addEventListener('click', ()=> {
    const form = document.getElementById('moveForm');
    const data = new FormData(form);
    fetch('/billiard_pos/tables/move_session.php', { method:'POST', body:data })
        .then(r=>r.json()).then(j=>{
            if (j.status === 'ok') {
                alert('Berhasil pindah: '+j.from_table+' -> '+j.to_table+'\nTarif baru: '+(j.new_tariff||''));
                location.reload();
            } else {
                alert(j.message || 'Gagal pindah meja');
            }
        }).catch(()=>alert('Gagal memanggil API'));
});
</script>

<!-- Start Session Modal -->
<div class="modal fade" id="startModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-secondary text-light">
      <div class="modal-header">
        <h5 class="modal-title">Mulai Sesi Meja</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="startForm">
      <div class="modal-body">
          <input type="hidden" name="table_id" value="">
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-member" data-bs-toggle="tab" data-bs-target="#pane-member" type="button" role="tab">Member</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-umum" data-bs-toggle="tab" data-bs-target="#pane-umum" type="button" role="tab">Umum</button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="pane-member" role="tabpanel">
                <label class="form-label">Cari / Pilih Member</label>
                <input id="memberSearchInput" list="memberList" class="form-control mb-2" placeholder="ketik nama/HP/kode" oninput="syncMemberSelect(this.value)">
                <datalist id="memberList">
                    <?php $mSelect = $pdo->query("SELECT * FROM members WHERE is_active = 1 ORDER BY name")->fetchAll(); ?>
                    <?php foreach ($mSelect as $m): ?>
                        <option value="<?php echo htmlspecialchars($m['name'].' - '.$m['phone'].' ('.$m['code'].')'); ?>" data-id="<?php echo $m['id']; ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <select name="member_id" class="form-select mb-3" id="memberSelect">
                    <?php foreach ($mSelect as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?> (<?php echo $m['phone']; ?>) - <?php echo $m['code']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tab-pane fade" id="pane-umum" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" class="form-control" name="customer_name" placeholder="Nama customer">
                </div>
                <div class="mb-3">
                    <label class="form-label">No HP</label>
                    <input type="text" class="form-control" name="customer_phone" placeholder="08xxx">
                </div>
            </div>
          </div>
          <label class="form-label mt-2">Paket Promo (opsional)</label>
          <select name="package_id" class="form-select">
              <option value="">Tanpa paket</option>
              <?php
              $pSelect = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY duration_minutes")->fetchAll();
              foreach ($pSelect as $p) {
                  echo '<option value="'.$p['id'].'">'.htmlspecialchars($p['name']).' - '.$p['duration_minutes'].' mnt - '.format_rupiah($p['special_price']).'</option>';
              }
              ?>
          </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-success">Mulai</button>
      </div>
      </form>
    </div>
  </div>
</div>
