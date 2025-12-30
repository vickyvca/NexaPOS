<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Team Standings</h1>
  <form method="get" class="flex flex-wrap gap-2 items-center">
    <input type="hidden" name="p" value="standings">
    <select class="border rounded px-3 py-2" name="season_id">
      <?php foreach ($seasons as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id']===$season_id)?'selected':'' ?>><?= h($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="border rounded px-3 py-2" name="swim_event_id">
      <option value="0" <?= $swim_event_id===0?'selected':'' ?>>All Events</option>
      <?php foreach ($events as $ev): ?>
        <option value="<?= (int)$ev['id'] ?>" <?= ((int)$ev['id']===$swim_event_id)?'selected':'' ?>><?= h($ev['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="border rounded px-3 py-2" name="meet_id">
      <option value="0" <?= ($meet_id===0)?'selected':'' ?>>All Meets</option>
      <?php foreach ($meets as $mt): ?>
        <option value="<?= (int)$mt['id'] ?>" <?= ((int)$mt['id']===$meet_id)?'selected':'' ?>><?= h($mt['name']) ?> (<?= h($mt['meet_on']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button class="bg-blue-600 text-white px-3 py-2 rounded" type="submit">Apply</button>
    <a class="bg-gray-100 px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=standings-export&season_id=<?= (int)$season_id ?>&swim_event_id=<?= (int)$swim_event_id ?>&meet_id=<?= (int)$meet_id ?>">Export CSV</a>
  </form>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead>
      <tr>
        <th class="p-2 border-b">Rank</th>
        <th class="p-2 border-b">Club</th>
        <th class="p-2 border-b">Gold</th>
        <th class="p-2 border-b">Silver</th>
        <th class="p-2 border-b">Bronze</th>
        <th class="p-2 border-b">Finalist</th>
        <th class="p-2 border-b">Medal Points</th>
      </tr>
    </thead>
    <tbody>
      <?php $rank=1; foreach ($rows as $r): ?>
        <tr>
          <td class="p-2 border-b"><?= $rank++ ?></td>
          <td class="p-2 border-b font-semibold"><?= h($r['club_name']) ?></td>
          <td class="p-2 border-b"><?= (int)$r['gold'] ?></td>
          <td class="p-2 border-b"><?= (int)$r['silver'] ?></td>
          <td class="p-2 border-b"><?= (int)$r['bronze'] ?></td>
          <td class="p-2 border-b"><?= (int)$r['finalist'] ?></td>
          <td class="p-2 border-b font-semibold"><?= (int)$r['total_medal_points'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($rows)): ?><div class="text-gray-500">No data.</div><?php endif; ?>
</div>
