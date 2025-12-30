<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Teams / Clubs</h1>
  <div class="text-sm text-gray-500">Coach/Organizer view</div>
  </div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <?php foreach ($clubs as $c): ?>
    <a class="block bg-white border rounded p-4 hover:shadow" href="<?= h(BASE_URL) ?>/public/index.php?p=team-club&club_id=<?= (int)$c['id'] ?>">
      <div class="text-lg font-semibold"><?= h($c['name']) ?></div>
      <div class="text-gray-600">City: <?= h($c['city'] ?? '-') ?></div>
      <div class="mt-2 text-sm">Athletes: <?= (int)($map[$c['id']] ?? 0) ?></div>
    </a>
  <?php endforeach; ?>
</div>

