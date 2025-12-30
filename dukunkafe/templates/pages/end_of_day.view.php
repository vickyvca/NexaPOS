<div class="space-y-4">
    <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-6">
        <h2 class="text-xl font-extrabold text-brand-800">Rekap Kas & Tutup Shift</h2>
        <p class="text-sm text-brand-700 mt-1">Periode: <?= htmlspecialchars($start) ?> &ndash; <?= htmlspecialchars($end) ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-5">
            <div class="text-sm text-brand-700">Tunai</div>
            <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($by_method['CASH'], 0, ',', '.') ?></div>
        </div>
        <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-5">
            <div class="text-sm text-brand-700">QRIS</div>
            <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($by_method['QRIS'], 0, ',', '.') ?></div>
        </div>
        <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-5">
            <div class="text-sm text-brand-700">Kartu</div>
            <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($by_method['CARD'], 0, ',', '.') ?></div>
        </div>
        <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-5">
            <div class="text-sm text-brand-700">Non Tunai Lain</div>
            <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($by_method['OTHER'], 0, ',', '.') ?></div>
        </div>

        <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-5">
            <div class="text-sm text-brand-700">Perkiraan Kas (di Laci)</div>
            <div class="text-xs text-brand-600">Opening + Tunai - Pengeluaran</div>
            <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($expected_cash, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="bg-white shadow-card border border-brand-100 rounded-xxl p-6">
        <h3 class="text-lg font-extrabold text-brand-800 mb-3">Tutup Shift</h3>
        <?php if ($active_session): ?>
            <p class="text-sm text-brand-700 mb-3">Masukkan jumlah kas fisik yang dihitung di laci. Perkiraan: <span class="font-semibold">Rp <?= number_format($expected_cash, 0, ',', '.') ?></span></p>
            <form method="POST" action="<?= base_url('end_of_day') ?>">
                <input type="hidden" name="action" value="end_session">
                <div class="flex items-center gap-3">
                    <input type="number" name="closing_cash" required class="w-60 px-3 py-2 border border-brand-200 rounded-lg" placeholder="Jumlah kas fisik">
                    <button type="submit" class="px-4 py-2 rounded-full bg-brand-600 text-white">Tutup Shift</button>
                </div>
            </form>
            <p class="text-xs text-brand-600 mt-2">Shift aktif sejak: <?= htmlspecialchars($active_session['opened_at']) ?></p>
        <?php else: ?>
            <p class="text-sm text-brand-700">Tidak ada shift aktif. Buka shift di Buku Kas untuk memulai sesi kas.</p>
        <?php endif; ?>
    </div>
</div>
