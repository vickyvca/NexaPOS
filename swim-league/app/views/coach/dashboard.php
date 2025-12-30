<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Dashboard Pelatih</h1>
  <form method="get" class="flex items-center gap-2">
    <input type="hidden" name="p" value="coach">
    <select class="border rounded px-3 py-2" name="club_id" onchange="this.form.submit()">
      <?php foreach ($clubs as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id']===$club_id)?'selected':'' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($club): ?>
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
  <div class="bg-gradient-to-br from-sky-600 to-sky-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Atlet</div>
    <div class="text-2xl font-semibold"><?= (int)$summary['athletes'] ?></div>
  </div>
  <div class="bg-gradient-to-br from-emerald-600 to-emerald-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Jarak Mingguan</div>
    <div class="text-2xl font-semibold"><?= number_format($summary['week_dist']/1000, 2) ?> km</div>
  </div>
  <div class="bg-gradient-to-br from-indigo-600 to-indigo-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Jarak Bulanan</div>
    <div class="text-2xl font-semibold"><?= number_format($summary['month_dist']/1000, 2) ?> km</div>
  </div>
  <div class="bg-gradient-to-br from-rose-600 to-rose-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Poin Medali</div>
    <div class="text-2xl font-semibold"><?= (int)$summary['medal_points'] ?></div>
  </div>
  <div class="bg-gradient-to-br from-amber-600 to-amber-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Rata-rata SWOLF (bulan ini)</div>
    <div class="text-2xl font-semibold"><?= $summary['avg_swolf'] ? number_format($summary['avg_swolf'], 0) : '-' ?></div>
  </div>
  <div class="bg-gradient-to-br from-fuchsia-600 to-fuchsia-400 text-white rounded-xl p-4 shadow">
    <div class="text-sm opacity-90">Rata-rata HR (bulan ini)</div>
    <div class="text-2xl font-semibold"><?= $summary['avg_hr'] ? number_format($summary['avg_hr'], 0) . ' bpm' : '-' ?></div>
  </div>
</div>

<div class="bg-white border rounded p-4">
  <h2 class="text-lg font-semibold mb-2">Podium & Hasil Terbaru</h2>
<?php $recent = DB::fetchAll('SELECT t.*, u.name AS athlete, se.name AS evname, m.meet_on
      FROM (
        SELECT r.*, 1 + (SELECT COUNT(*) FROM results r2 WHERE r2.race_id=r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms) AS pos
        FROM results r
      ) t
      JOIN athletes a ON t.athlete_id=a.id
      JOIN users u ON a.user_id=u.id
      JOIN races rc ON t.race_id=rc.id
      JOIN swim_events se ON rc.swim_event_id=se.id
      JOIN meets m ON rc.meet_id=m.id
      WHERE a.club_id=? AND t.status="OK" ORDER BY t.id DESC LIMIT 20', [$club_id]); ?>
  <table class="w-full text-left text-sm">
    <thead><tr><th class="p-2 border-b">Atlet</th><th class="p-2 border-b">Nomor</th><th class="p-2 border-b">Waktu</th><th class="p-2 border-b">Medali</th><th class="p-2 border-b">Poin</th></tr></thead>
    <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td class="p-2 border-b"><?= h($r['athlete']) ?></td>
          <td class="p-2 border-b"><?= h($r['evname']) ?> (<?= h($r['meet_on']) ?>)</td>
          <td class="p-2 border-b"><?= ms_to_time((int)$r['time_ms']) ?></td>
          <?php $label = ($r['pos']==1?'GOLD':($r['pos']==2?'SILVER':($r['pos']==3?'BRONZE':($r['pos']>=4&&$r['pos']<=8?'FINALIST':'-'))));
                $pts = ($r['pos']==1?25:($r['pos']==2?18:($r['pos']==3?15:($r['pos']>=4&&$r['pos']<=8?10:0)))); ?>
          <td class="p-2 border-b"><?= h($label) ?></td>
          <td class="p-2 border-b"><?= (int)$pts ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
  <div class="text-gray-500">Belum ada klub.</div>
<?php endif; ?>
