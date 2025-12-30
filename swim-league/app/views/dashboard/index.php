<h1 class="text-2xl font-bold mb-4">Dashboard Atlet</h1>
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
  <div class="bg-gradient-to-br from-sky-600 to-sky-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Jarak Mingguan</div>
    <div class="text-2xl font-semibold flex items-baseline gap-2"><?= number_format(($stats['week_dist'] ?? 0)/1000, 2) ?><span class="text-xs">km</span></div>
  </div>
  <div class="bg-gradient-to-br from-emerald-600 to-emerald-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Jarak Bulanan</div>
    <div class="text-2xl font-semibold flex items-baseline gap-2"><?= number_format(($stats['month_dist'] ?? 0)/1000, 2) ?><span class="text-xs">km</span></div>
  </div>
  <div class="bg-gradient-to-br from-indigo-600 to-indigo-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Pace Terbaik /100m</div>
    <div class="text-2xl font-semibold"><?= isset($stats['best_pace100']) && $stats['best_pace100'] ? ms_to_time((int)$stats['best_pace100']) : '-' ?></div>
  </div>
  <div class="bg-gradient-to-br from-rose-600 to-rose-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Durasi Bulanan</div>
    <div class="text-2xl font-semibold"><?php $md = (int)($stats['month_duration'] ?? 0); echo ms_to_time($md); ?></div>
  </div>
  <div class="bg-gradient-to-br from-amber-600 to-amber-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Rata-rata SWOLF (bulan ini)</div>
    <div class="text-2xl font-semibold"><?= isset($stats['avg_swolf']) && $stats['avg_swolf'] ? number_format($stats['avg_swolf'],0) : '-' ?></div>
  </div>
  <div class="bg-gradient-to-br from-fuchsia-600 to-fuchsia-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Rata-rata HR (bulan ini)</div>
    <div class="text-2xl font-semibold"><?= isset($stats['avg_hr']) && $stats['avg_hr'] ? number_format($stats['avg_hr'],0) . ' bpm' : '-' ?></div>
  </div>
</div>

<div class="bg-white border rounded p-4 mb-6">
  <h2 class="text-lg font-semibold mb-2">Aksi Cepat</h2>
  <div class="flex flex-wrap gap-2">
    <a class="px-3 py-2 rounded bg-blue-600 text-white" href="<?= h(BASE_URL) ?>/public/index.php?p=activities-create">➕ Tambah Aktivitas</a>
    <a class="px-3 py-2 rounded bg-green-600 text-white" href="<?= h(BASE_URL) ?>/public/index.php?p=activities-import">⬆️ Import CSV</a>
    <a class="px-3 py-2 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=leaderboard">🏆 Papan Peringkat</a>
  </div>
</div>

<div class="bg-white border rounded p-4">
  <h2 class="text-xl font-semibold mb-3">Aktivitas Terbaru</h2>
  <table class="w-full text-left">
    <thead>
      <tr><th class="p-2 border-b">Tanggal</th><th class="p-2 border-b">Jarak</th><th class="p-2 border-b">Durasi</th><th class="p-2 border-b">Pace/100m</th></tr>
    </thead>
    <tbody>
      <?php foreach ($latest as $a): ?>
        <tr>
          <td class="p-2 border-b"><?= h($a['activity_on']) ?></td>
          <td class="p-2 border-b"><?= (int)$a['distance_m'] ?> m</td>
          <td class="p-2 border-b"><?= ms_to_time((int)$a['duration_ms']) ?></td>
          <td class="p-2 border-b"><?= pace100_str((int)$a['duration_ms'], (int)$a['distance_m']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
