<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Add Event</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label class="block mb-2">Season
      <select class="w-full border rounded px-3 py-2" name="season_id" required>
        <option value="">-- select --</option>
        <?php foreach ($seasons as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="block mb-4">Event Name
      <input class="w-full border rounded px-3 py-2" type="text" name="name" required>
    </label>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Create</button>
  </form>
</div>

