<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Program Latihan (Coach)</h1>
  <a class="bg-indigo-600 text-white px-3 py-2 rounded" href="<?= h(BASE_URL) ?>/public/index.php?p=coach-program-create">Buat Program</a>
</div>

<form method="get" class="flex flex-wrap gap-2 items-center mb-4">
  <input type="hidden" name="p" value="coach-programs">
  <label>Klub
    <select class="border rounded px-3 py-2" name="club_id" onchange="this.form.submit()">
      <?php foreach ($clubs as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id']===$club_id)?'selected':'' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Tanggal
    <input class="border rounded px-3 py-2" type="date" name="on" value="<?= h($date) ?>" onchange="this.form.submit()">
  </label>
</form>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left text-sm">
    <thead><tr><th class="p-2 border-b">Atlet</th><th class="p-2 border-b">Judul</th><th class="p-2 border-b">Target</th><th class="p-2 border-b">Catatan</th><th class="p-2 border-b">Status</th></tr></thead>
    <tbody>
      <?php foreach ($workouts as $w): ?>
        <tr>
          <td class="p-2 border-b"><?= h($w['athlete_name']) ?></td>
          <td class="p-2 border-b font-semibold"><?= h($w['title']) ?></td>
          <td class="p-2 border-b">
            <?= $w['target_distance_m'] ? (int)$w['target_distance_m'] . ' m' : '-' ?>
            <?php if ($w['stroke_type']): ?> · <?= h($w['stroke_type']) ?><?php endif; ?>
            <?php if ($w['target_swolf']): ?> · SWOLF <?= (int)$w['target_swolf'] ?><?php endif; ?>
          </td>
          <td class="p-2 border-b text-gray-600">
            <?= nl2br(h($w['description'])) ?>
          </td>
          <td class="p-2 border-b"><?= h($w['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($workouts)): ?><div class="text-gray-500">Belum ada program di tanggal ini.</div><?php endif; ?>
</div>

