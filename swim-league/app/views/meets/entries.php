<div class="mb-4">
  <h1 class="text-2xl font-bold">Race Entries</h1>
  <?php if (!empty($race)): ?>
    <div class="text-gray-600">Race: <?= h($race['swim_event']) ?> (Heat <?= (int)$race['heat_no'] ?>)</div>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-2">Add Entry</h2>
    <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label class="block mb-2">Athlete
        <select class="w-full border rounded px-3 py-2" name="athlete_id" required>
          <?php foreach ($athletes as $a): ?>
            <option value="<?= (int)$a['id'] ?>"><?= h($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block mb-4">Lane
        <input class="w-full border rounded px-3 py-2" type="number" name="lane" required>
      </label>
      <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Add</button>
    </form>
    <form class="mt-3" method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="auto_seed">
      <div class="flex flex-wrap gap-2 items-center">
        <label class="text-sm">Method
          <select class="border rounded px-2 py-1" name="seed_method">
            <option value="center">Center-out</option>
            <option value="simple">Simple 1..N</option>
          </select>
        </label>
        <label class="text-sm">Lane Count
          <input class="border rounded px-2 py-1 w-20" type="number" name="lane_count" value="8" min="1" max="16">
        </label>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Auto Seed Lanes</button>
      </div>
      <div class="text-xs text-gray-500 mt-1">Seeds by best historical time; Center-out puts fastest in center lanes.</div>
    </form>
  </div>

  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-2">Entries</h2>
    <table class="w-full text-left">
      <thead><tr><th class="p-2 border-b">Lane</th><th class="p-2 border-b">Athlete</th></tr></thead>
      <tbody>
        <?php foreach ($entries as $e): ?>
          <tr><td class="p-2 border-b"><?= (int)$e['lane'] ?></td><td class="p-2 border-b"><?= h($e['athlete_name']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-4 bg-white border rounded p-4">
  <h2 class="font-semibold mb-2">Heats Management</h2>
  <form method="post" class="flex flex-wrap gap-2 items-center">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="auto_split">
    <label class="text-sm">Lane Count <input class="border rounded px-2 py-1 w-20" type="number" name="lane_count" value="8" min="1" max="16"></label>
    <button class="bg-amber-600 text-white px-4 py-2 rounded" type="submit">Auto Split Heats</button>
    <div class="text-xs text-gray-500">Splits entries into multiple heats with center-out lane seeding.</div>
  </form>
</div>
