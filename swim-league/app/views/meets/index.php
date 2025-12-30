<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Meets</h1>
  <a class="bg-blue-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=meet-create">Create Meet</a>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead><tr><th class="p-2 border-b">Meet</th><th class="p-2 border-b">Date</th><th class="p-2 border-b">Event</th><th class="p-2 border-b">Season</th></tr></thead>
    <tbody>
      <?php foreach ($meets as $m): ?>
        <tr>
          <td class="p-2 border-b"><?= h($m['name']) ?></td>
          <td class="p-2 border-b"><?= h($m['meet_on']) ?></td>
          <td class="p-2 border-b"><?= h($m['event_name']) ?></td>
          <td class="p-2 border-b"><?= h($m['season_name']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

