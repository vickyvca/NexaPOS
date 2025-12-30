<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Import Activities CSV</h1>
  <p class="text-sm mb-3">Headers required: activity_on, distance_m, duration_ms, pool_length_m, stroke_type, avg_hr, max_hr, calories</p>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="file" name="csv" accept=".csv" required>
    <button class="bg-green-600 text-white px-4 py-2 rounded ml-2" type="submit">Upload</button>
  </form>
</div>

