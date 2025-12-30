<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Add Season / League</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label class="block mb-2">Name
      <input class="w-full border rounded px-3 py-2" type="text" name="name" required>
    </label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <label>Start Date<input class="w-full border rounded px-3 py-2" type="date" name="start_date" required></label>
      <label>End Date<input class="w-full border rounded px-3 py-2" type="date" name="end_date" required></label>
    </div>
    <div class="mt-4">
      <button class="bg-emerald-600 text-white px-4 py-2 rounded" type="submit">Create</button>
    </div>
  </form>
</div>

