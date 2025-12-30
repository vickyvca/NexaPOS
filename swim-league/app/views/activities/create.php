<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Add Activity</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <label>Date<input class="w-full border rounded px-3 py-2" type="date" name="activity_on" value="<?= h(date('Y-m-d')) ?>" required></label>
      <label>Distance (m)<input class="w-full border rounded px-3 py-2" type="number" name="distance_m" required></label>
      <label>Duration (ms)<input class="w-full border rounded px-3 py-2" type="number" name="duration_ms" required></label>
      <label>Pool Length (m)<input class="w-full border rounded px-3 py-2" type="number" name="pool_length_m" value="<?= (int)DEFAULT_POOL_LENGTH_M ?>"></label>
      <label>Stroke<input class="w-full border rounded px-3 py-2" type="text" name="stroke_type" placeholder="FREE/BACK/BREAST/FLY/IM"></label>
      <label>Avg HR<input class="w-full border rounded px-3 py-2" type="number" name="avg_hr"></label>
      <label>Max HR<input class="w-full border rounded px-3 py-2" type="number" name="max_hr"></label>
      <label>Calories<input class="w-full border rounded px-3 py-2" type="number" name="calories"></label>
      <label>SWOLF<input class="w-full border rounded px-3 py-2" type="number" name="swolf"></label>
    </div>
    <div class="mt-4">
      <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Save</button>
    </div>
  </form>
</div>
