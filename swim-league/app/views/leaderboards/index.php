<h1 class="text-2xl font-bold mb-4">Leaderboard</h1>
<form class="bg-white border rounded p-4 mb-4" method="get">
  <input type="hidden" name="p" value="leaderboard">
  <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
    <label>Season
      <select class="w-full border rounded px-3 py-2" name="season_id">
        <?php foreach ($seasons as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id']===$season_id)?'selected':'' ?>><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Event
      <select class="w-full border rounded px-3 py-2" name="swim_event_id">
        <?php foreach ($events as $e): ?>
          <option value="<?= (int)$e['id'] ?>" <?= ((int)$e['id']===$swim_event_id)?'selected':'' ?>><?= h($e['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Gender
      <select class="w-full border rounded px-3 py-2" name="gender">
        <option value="">All</option>
        <option value="M" <?= $gender==='M'?'selected':'' ?>>Male</option>
        <option value="F" <?= $gender==='F'?'selected':'' ?>>Female</option>
      </select>
    </label>
    <label>Age Group
      <select class="w-full border rounded px-3 py-2" name="age_group">
        <option value="">All</option>
        <?php foreach (['U12','U14','U16','U18','Open'] as $ag): ?>
          <option value="<?= h($ag) ?>" <?= $age_group===$ag?'selected':'' ?>><?= h($ag) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="mt-3">
    <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Filter</button>
  </div>
  <div class="mt-2 text-sm text-gray-500">
    API: <a class="text-blue-600" href="<?= h(BASE_URL) ?>/public/index.php?p=api-leaderboards&season_id=<?= (int)$season_id ?>&swim_event_id=<?= (int)$swim_event_id ?>">/api/leaderboards</a>
  </div>
</form>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead><tr><th class="p-2 border-b">Rank</th><th class="p-2 border-b">Athlete</th><th class="p-2 border-b">Gender</th><th class="p-2 border-b">Total Points</th><th class="p-2 border-b">Best Time</th></tr></thead>
    <tbody>
      <?php $rank=1; foreach ($rows as $r): ?>
        <tr>
          <td class="p-2 border-b"><?= $rank++ ?></td>
          <td class="p-2 border-b"><?= h($r['athlete_name']) ?></td>
          <td class="p-2 border-b"><?= h($r['gender']) ?></td>
          <td class="p-2 border-b"><?= (int)$r['total_points'] ?></td>
          <td class="p-2 border-b"><?= ms_to_time((int)$r['best_time_ms']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

