<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Events</h1>
  <a class="bg-indigo-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=event-create">Add Event</a>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left">
    <thead><tr><th class="p-2 border-b">Event</th><th class="p-2 border-b">Season</th></tr></thead>
    <tbody>
      <?php foreach ($events as $e): ?>
        <tr>
          <td class="p-2 border-b font-semibold"><?= h($e['name']) ?></td>
          <td class="p-2 border-b"><?= h($e['season_name']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($events)): ?><div class="text-gray-500">No events yet.</div><?php endif; ?>
  </div>

