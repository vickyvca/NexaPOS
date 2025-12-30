<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Leagues / Seasons</h1>
  <a class="bg-emerald-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=season-create">Add Season</a>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead><tr><th class="p-2 border-b">Name</th><th class="p-2 border-b">Start</th><th class="p-2 border-b">End</th></tr></thead>
    <tbody>
      <?php foreach ($seasons as $s): ?>
        <tr>
          <td class="p-2 border-b font-semibold"><?= h($s['name']) ?></td>
          <td class="p-2 border-b"><?= h($s['start_date']) ?></td>
          <td class="p-2 border-b"><?= h($s['end_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($seasons)): ?><div class="text-gray-500">No seasons yet.</div><?php endif; ?>
  </div>

