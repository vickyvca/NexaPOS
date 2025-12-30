<div class="max-w-xl mx-auto">
    <?php if (!empty($active_session)): ?>
        <div class="bg-white p-6 rounded-xxl shadow-card border border-brand-100">
            <h3 class="text-lg font-extrabold text-brand-800">Shift Sudah Aktif</h3>
            <p class="text-sm text-brand-700 mt-1">Kasir: <?= htmlspecialchars($active_session['cashier_name'] ?? '-') ?>, sejak <?= date('d M Y H:i', strtotime($active_session['opened_at'])) ?></p>
            <div class="mt-4 flex gap-2">
                <a href="<?= base_url('pos') ?>" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-full font-semibold">Ke POS</a>
                <a href="<?= base_url('end_of_day') ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-full font-semibold">Tutup Shift</a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white p-6 rounded-xxl shadow-card border border-brand-100">
            <h3 class="text-lg font-extrabold text-brand-800">Mulai Shift Kas</h3>
            <p class="text-sm text-brand-700 mt-1">Masukkan saldo awal kas untuk memulai shift.</p>
            <form method="POST" action="<?= base_url('shift/start') ?>" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="start_session">
                <div>
                    <label for="opening_cash" class="block text-sm font-medium text-brand-700">Saldo Awal Kas (Rp)</label>
                    <input type="number" name="opening_cash" id="opening_cash" min="0" step="1" required class="block w-full px-3 py-3 border border-brand-200 rounded-xl text-brand-900" placeholder="0">
                </div>
                <button type="submit" class="w-full px-4 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-full font-semibold">Mulai Shift</button>
            </form>
        </div>
    <?php endif; ?>
</div>

