<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Create Meet</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label class="block mb-2">Event
      <select class="w-full border rounded px-3 py-2" name="event_id" required>
        <option value="">-- select --</option>
        <?php foreach ($events as $e): ?>
          <option value="<?= (int)$e['id'] ?>"><?= h($e['season'] . ' - ' . $e['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="block mb-2">Name
      <input class="w-full border rounded px-3 py-2" type="text" name="name" required>
    </label>
    <label class="block mb-2">Date
      <input class="w-full border rounded px-3 py-2" type="date" name="meet_on" value="<?= h(date('Y-m-d')) ?>" required>
    </label>
    <label class="block mb-4">Venue
      <input class="w-full border rounded px-3 py-2" type="text" name="venue">
    </label>
    <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Create</button>
  </form>
</div>

