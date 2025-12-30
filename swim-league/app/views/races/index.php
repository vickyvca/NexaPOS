<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Races</h1>
  <a class="bg-indigo-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=race-create">Create Race</a>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead><tr><th class="p-2 border-b">Meet</th><th class="p-2 border-b">Date</th><th class="p-2 border-b">Event</th><th class="p-2 border-b">Round</th><th class="p-2 border-b">Heat</th><th class="p-2 border-b">Entries</th><th class="p-2 border-b">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($races as $r): ?>
        <?php $cnt = DB::fetch('SELECT COUNT(*) c FROM race_entries WHERE race_id=?', [$r['id']]); ?>
        <tr>
          <td class="p-2 border-b"><?= h($r['meet_name']) ?></td>
          <td class="p-2 border-b"><?= h($r['meet_on']) ?></td>
          <td class="p-2 border-b"><?= h($r['swim_event']) ?></td>
          <td class="p-2 border-b"><?= h($r['round_name']) ?></td>
          <td class="p-2 border-b"><?= (int)$r['heat_no'] ?></td>
          <td class="p-2 border-b"><?= (int)($cnt['c'] ?? 0) ?></td>
          <td class="p-2 border-b">
            <a class="text-blue-600" href="<?= h(BASE_URL) ?>/public/index.php?p=entries&race_id=<?= (int)$r['id'] ?>">Entries</a>
            <span class="text-gray-400">|</span>
            <a class="text-green-600" href="<?= h(BASE_URL) ?>/public/index.php?p=results-import&race_id=<?= (int)$r['id'] ?>">Import Results</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($races)): ?><div class="text-gray-500">No races yet.</div><?php endif; ?>
</div>

