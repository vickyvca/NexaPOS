<div class="max-w-2xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Selesaikan Program</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <label>Tanggal
        <input class="w-full border rounded px-3 py-2" type="date" name="activity_on" value="<?= h($w['planned_on']) ?>">
      </label>
      <label>Jarak (m)
        <input class="w-full border rounded px-3 py-2" type="number" name="distance_m" value="<?= (int)($w['target_distance_m'] ?? 0) ?>">
      </label>
      <label>Durasi (ms)
        <input class="w-full border rounded px-3 py-2" type="number" name="duration_ms" placeholder="Contoh: 1800000">
      </label>
      <label>Panjang Kolam (m)
        <input class="w-full border rounded px-3 py-2" type="number" name="pool_length_m" value="<?= (int)DEFAULT_POOL_LENGTH_M ?>">
      </label>
      <label>Gaya
        <input class="w-full border rounded px-3 py-2" type="text" name="stroke_type" value="<?= h($w['stroke_type'] ?? '') ?>">
      </label>
      <label>Rata-rata HR
        <input class="w-full border rounded px-3 py-2" type="number" name="avg_hr">
      </label>
      <label>HR Maksimum
        <input class="w-full border rounded px-3 py-2" type="number" name="max_hr">
      </label>
      <label>Kalori
        <input class="w-full border rounded px-3 py-2" type="number" name="calories">
      </label>
      <label>SWOLF
        <input class="w-full border rounded px-3 py-2" type="number" name="swolf" value="<?= (int)($w['target_swolf'] ?? 0) ?>">
      </label>
    </div>
    <div class="mt-4">
      <button class="bg-emerald-600 text-white px-4 py-2 rounded" type="submit">Simpan & Tandai Selesai</button>
    </div>
  </form>
</div>

