<form method="POST" action="<?= base_url('branch_settings') ?>" enctype="multipart/form-data" class="space-y-6">
  <input type="hidden" name="branch_id" value="<?= (int)$branch_id ?>">
  <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-extrabold text-brand-800">Pengaturan Cabang</h3>
        <p class="text-sm text-brand-700">Atur nama, alamat, dan logo untuk cabang ini.</p>
      </div>
      <div>
        <label class="text-sm text-brand-700 mr-2">Cabang</label>
        <select onchange="location.href='<?= base_url('branch_settings') ?>&branch_id='+this.value" class="border border-brand-200 rounded-xl px-3 py-2">
          <?php foreach (($branches ?? []) as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id']===(int)$branch_id?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
      <div class="sm:col-span-2">
        <label class="block text-sm text-brand-700">Nama Toko</label>
        <input name="cafe_name" value="<?= htmlspecialchars($settings['cafe_name'] ?? '') ?>" class="mt-1 w-full border border-brand-200 rounded-xl px-3 py-2" required>
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm text-brand-700">Alamat</label>
        <textarea name="cafe_address" rows="3" class="mt-1 w-full border border-brand-200 rounded-xl px-3 py-2"><?= htmlspecialchars($settings['cafe_address'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-sm text-brand-700">Logo (opsional)</label>
        <input type="file" name="cafe_logo" class="mt-1 block w-full text-sm text-brand-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
      </div>
      <div class="flex items-end">
        <button type="submit" class="px-5 py-2 rounded-full bg-brand-600 text-white font-semibold">Simpan</button>
      </div>
    </div>
  </div>
</form>

