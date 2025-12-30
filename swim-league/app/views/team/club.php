<h1 class="text-2xl font-bold mb-4">Club: <?= h($club['name']) ?></h1>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left text-sm">
    <thead><tr>
      <th class="p-2 border-b">Athlete</th>
      <th class="p-2 border-b">Gender</th>
      <th class="p-2 border-b">Birthdate</th>
      <th class="p-2 border-b">Total Distance</th>
      <th class="p-2 border-b">Last Activity</th>
      <th class="p-2 border-b">Race Count</th>
      <th class="p-2 border-b">Perf Points</th>
      <th class="p-2 border-b">Medals</th>
      <th class="p-2 border-b">Medal Points</th>
    </tr></thead>
    <tbody>
      <?php foreach ($athletes as $a): ?>
        <tr>
          <td class="p-2 border-b font-medium"><?= h($a['user_name']) ?><div class="text-gray-500 text-xs"><?= h($a['email']) ?></div></td>
          <td class="p-2 border-b"><?= h($a['gender']) ?></td>
          <td class="p-2 border-b"><?= h($a['birthdate']) ?></td>
          <td class="p-2 border-b"><?= number_format($a['total_dist']/1000, 2) ?> km</td>
          <td class="p-2 border-b"><?= h($a['last_activity_on'] ?: '-') ?></td>
          <td class="p-2 border-b"><?= (int)$a['races_count'] ?></td>
          <td class="p-2 border-b"><?= (int)$a['total_points'] ?></td>
          <?php $med = DB::fetch('SELECT 
               SUM(CASE WHEN pos=1 THEN 1 ELSE 0 END) g,
               SUM(CASE WHEN pos=2 THEN 1 ELSE 0 END) s,
               SUM(CASE WHEN pos=3 THEN 1 ELSE 0 END) b,
               SUM(CASE WHEN pos BETWEEN 4 AND 8 THEN 1 ELSE 0 END) f,
               SUM(CASE pos WHEN 1 THEN 25 WHEN 2 THEN 18 WHEN 3 THEN 15 WHEN 4 THEN 10 WHEN 5 THEN 10 WHEN 6 THEN 10 WHEN 7 THEN 10 WHEN 8 THEN 10 ELSE 0 END) mp
            FROM (
              SELECT r.*, 1 + (SELECT COUNT(*) FROM results r2 WHERE r2.race_id=r.race_id AND r2.status="OK" AND r2.time_ms < r.time_ms) AS pos
              FROM results r WHERE r.athlete_id=? AND r.status="OK"
            ) t', [$a['id']]); ?>
          <td class="p-2 border-b text-sm">🥇 <?= (int)($med['g']??0) ?> · 🥈 <?= (int)($med['s']??0) ?> · 🥉 <?= (int)($med['b']??0) ?> · F <?= (int)($med['f']??0) ?></td>
          <td class="p-2 border-b"><?= (int)($med['mp']??0) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
