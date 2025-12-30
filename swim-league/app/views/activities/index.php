<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Activities</h1>
  <div class="space-x-2">
    <a class="bg-blue-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=activities-create">Add Activity</a>
    <a class="bg-green-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=activities-import">Import CSV</a>
  </div>
</div>

<div class="bg-white border rounded p-4 mb-4">
  <h2 class="font-semibold mb-2">This Month</h2>
  <div>Total Distance: <?= number_format(($summary['dist'] ?? 0)/1000, 2) ?> km</div>
  <div>Best Pace /100m: <?= isset($summary['best_pace100']) && $summary['best_pace100'] ? ms_to_time((int)$summary['best_pace100']) : '-' ?></div>
  </div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead>
      <tr><th class="p-2 border-b">Date</th><th class="p-2 border-b">Distance</th><th class="p-2 border-b">Duration</th><th class="p-2 border-b">Pace/100m</th><th class="p-2 border-b">Stroke</th></tr>
    </thead>
    <tbody>
      <?php foreach ($activities as $a): ?>
        <tr>
          <td class="p-2 border-b"><?= h($a['activity_on']) ?></td>
          <td class="p-2 border-b"><?= (int)$a['distance_m'] ?> m</td>
          <td class="p-2 border-b"><?= ms_to_time((int)$a['duration_ms']) ?></td>
          <td class="p-2 border-b"><?= pace100_str((int)$a['duration_ms'], (int)$a['distance_m']) ?></td>
          <td class="p-2 border-b"><?= h($a['stroke_type'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

