<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Scan Achievements</h1>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <button class="bg-emerald-600 text-white px-3 py-2 rounded" type="submit">Run Scan</button>
  </form>
  </div>

<?php if (!empty($ok)): ?><div class="mb-3 text-emerald-700"><?= h($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="mb-3 text-red-600"><?= h($error) ?></div><?php endif; ?>

<?php if (!empty($report)): ?>
  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-2">Report</h2>
    <ul class="list-disc pl-6 text-sm">
      <?php foreach ($report as $line): ?>
        <li><?= h($line) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

