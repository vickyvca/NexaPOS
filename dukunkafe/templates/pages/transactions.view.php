<div class="space-y-4">
  <form method="GET" action="<?= base_url('transactions') ?>" class="bg-white p-4 rounded-xxl shadow-card border border-brand-100">
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
      <div class="sm:col-span-2">
        <label class="block text-xs text-brand-700">Cari</label>
        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Order #, Pelanggan, Meja" class="w-full px-3 py-2 rounded-lg border border-brand-200" />
      </div>
      <div>
        <label class="block text-xs text-brand-700">Dari</label>
        <input type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>" class="w-full px-3 py-2 rounded-lg border border-brand-200" />
      </div>
      <div>
        <label class="block text-xs text-brand-700">Sampai</label>
        <input type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>" class="w-full px-3 py-2 rounded-lg border border-brand-200" />
      </div>
      <div>
        <label class="block text-xs text-brand-700">Status</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-brand-200">
          <?php $statuses = ['CLOSED' => 'CLOSED', 'OPEN' => 'OPEN', 'ALL' => 'SEMUA']; ?>
          <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-3 flex justify-end">
      <button type="submit" class="px-4 py-2 rounded-full bg-brand-600 text-white">Terapkan</button>
    </div>
  </form>

  <div class="bg-white rounded-xxl shadow-card border border-brand-100">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-brand-100">
        <thead class="bg-brand-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Waktu</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Order</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Channel</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Meja/Pelanggan</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Total</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Status</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-brand-100">
          <?php if (empty($orders)): ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-brand-600">Tidak ada data.</td></tr>
          <?php endif; ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="px-4 py-2 text-sm text-brand-700"><?= htmlspecialchars($o['created_at']) ?></td>
              <td class="px-4 py-2 font-semibold text-brand-900"><?= htmlspecialchars($o['order_no']) ?></td>
              <td class="px-4 py-2 text-sm"><span class="px-2 py-0.5 rounded-full <?= $o['channel']==='DINE_IN'?'bg-blue-100 text-blue-800':'bg-green-100 text-green-800' ?>"><?= htmlspecialchars($o['channel']) ?></span></td>
              <td class="px-4 py-2 text-sm text-brand-700"><?= htmlspecialchars($o['table_name'] ?: 'Take Away') ?><?= $o['customer_name'] ? (' · ' . htmlspecialchars($o['customer_name'])) : '' ?></td>
              <td class="px-4 py-2 text-right font-semibold">Rp <?= number_format($o['total'], 0, ',', '.') ?></td>
              <td class="px-4 py-2 text-sm">
                <span class="px-2 py-0.5 rounded-full <?= $o['status']==='CLOSED'?'bg-emerald-100 text-emerald-800':'bg-amber-100 text-amber-800' ?>"><?= htmlspecialchars($o['status']) ?></span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <a target="_blank" href="<?= base_url('receipt?id=' . urlencode($o['id'])) ?>" class="inline-flex items-center px-3 py-1.5 rounded-full bg-brand-600 text-white text-xs font-semibold">
                  <i class="fa-solid fa-print mr-1"></i> Cetak Ulang Nota
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

