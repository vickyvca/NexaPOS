<div class="space-y-4">
  <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
    <h3 class="text-lg font-extrabold text-brand-800 mb-3">Tambah Cabang</h3>
    <form method="POST" action="<?= base_url('branches') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="hidden" name="action" value="create">
      <div class="md:col-span-1">
        <label class="block text-sm text-brand-700">Kode</label>
        <input name="code" required class="mt-1 w-full border border-brand-200 rounded-xl px-3 py-2" placeholder="KODE">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-brand-700">Nama</label>
        <input name="name" required class="mt-1 w-full border border-brand-200 rounded-xl px-3 py-2" placeholder="Nama Cabang">
      </div>
      <div class="md:col-span-1 flex items-end">
        <button type="submit" class="px-4 py-2 rounded-full bg-brand-600 text-white font-semibold w-full">Tambah</button>
      </div>
    </form>
  </div>

  <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
    <h3 class="text-lg font-extrabold text-brand-800 mb-3">Daftar Cabang</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-brand-100">
        <thead class="bg-brand-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Kode</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Nama</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Status</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-brand-100">
          <?php foreach (($branches ?? []) as $b): ?>
          <tr>
            <td class="px-4 py-2 text-sm text-brand-900"><?= htmlspecialchars($b['code'] ?? '') ?></td>
            <td class="px-4 py-2 text-sm text-brand-900"><?= htmlspecialchars($b['name'] ?? '') ?></td>
            <td class="px-4 py-2 text-sm">
              <span class="px-2 py-0.5 rounded-full text-xs <?= ($b['active'] ?? 0) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' ?>"><?= ($b['active'] ?? 0) ? 'Aktif' : 'Nonaktif' ?></span>
            </td>
            <td class="px-4 py-2 text-right">
              <form method="POST" action="<?= base_url('branches') ?>" class="inline-flex items-center gap-2">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)($b['id'] ?? 0) ?>">
                <input name="code" value="<?= htmlspecialchars($b['code'] ?? '') ?>" class="w-28 border border-brand-200 rounded-lg px-2 py-1 text-sm">
                <input name="name" value="<?= htmlspecialchars($b['name'] ?? '') ?>" class="w-48 border border-brand-200 rounded-lg px-2 py-1 text-sm">
                <label class="text-sm text-brand-700 inline-flex items-center gap-1">
                  <input type="checkbox" name="active" value="1" <?= ($b['active'] ?? 0) ? 'checked' : '' ?>> Aktif
                </label>
                <button type="submit" class="px-3 py-1.5 rounded-full bg-brand-600 text-white text-sm">Simpan</button>
              </form>
            </td>
          </tr>
          <?php endforeach; if (empty($branches)): ?>
            <tr><td colspan="4" class="px-4 py-6 text-center text-brand-600">Belum ada cabang.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

