<div class="max-w-2xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Buat Program Latihan</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <div class="mb-3">
      <label>Template
        <select class="w-full border rounded px-3 py-2" id="template">
          <option value="">(tanpa template)</option>
          <?php foreach ($templates as $t): ?>
            <option value='<?= h(json_encode($t)) ?>'><?= h($t['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <label>Klub
        <select class="w-full border rounded px-3 py-2" name="club_id" id="club_id">
          <?php foreach ($clubs as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Tanggal
        <input class="w-full border rounded px-3 py-2" type="date" name="planned_on" value="<?= h(date('Y-m-d')) ?>">
      </label>
      <label>Judul
        <input class="w-full border rounded px-3 py-2" type="text" name="title" placeholder="Contoh: Aerobic Endurance">
      </label>
      <label>Target Jarak (m)
        <input class="w-full border rounded px-3 py-2" type="number" name="target_distance_m" placeholder="Contoh: 3000">
      </label>
      <label>Gaya
        <select class="w-full border rounded px-3 py-2" name="stroke_type">
          <option value="">(Bebas)</option>
          <?php foreach (["FREE","BACK","BREAST","FLY","IM"] as $st): ?>
            <option value="<?= h($st) ?>"><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Target SWOLF
        <input class="w-full border rounded px-3 py-2" type="number" name="target_swolf" placeholder="Contoh: 40">
      </label>
      <label class="md:col-span-2">Deskripsi
        <textarea class="w-full border rounded px-3 py-2" name="description" rows="3" placeholder="Detail set: pemanasan, main set, cooldown"></textarea>
      </label>
    </div>
    <div class="mt-4 bg-gray-50 border rounded p-3">
      <div class="font-semibold mb-2">Penugasan</div>
      <label class="mr-4"><input type="radio" name="assign" value="club" checked> Semua atlet di klub</label>
      <label class="mr-2"><input type="radio" name="assign" value="athlete"> Atlet tertentu</label>
      <select class="border rounded px-3 py-2 ml-2" name="athlete_id">
        <?php foreach ($athletes as $a): ?>
          <option value="<?= (int)$a['id'] ?>"><?= h($a['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mt-4 bg-gray-50 border rounded p-3">
      <div class="font-semibold mb-2">Ulang Harian</div>
      <label>Jumlah hari
        <input class="border rounded px-3 py-2 w-24" type="number" name="repeat_days" value="1" min="1" max="14">
      </label>
    </div>
    <div class="mt-4">
      <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Simpan Program</button>
    </div>
  </form>
</div>

<script>
  const sel = document.getElementById('template');
  sel.addEventListener('change', () => {
    if (!sel.value) return;
    const t = JSON.parse(sel.options[sel.selectedIndex].value);
    document.querySelector('[name=title]').value = t.title || '';
    document.querySelector('[name=description]').value = t.description || '';
    document.querySelector('[name=target_distance_m]').value = t.target_distance_m || '';
    document.querySelector('[name=stroke_type]').value = t.stroke_type || '';
    document.querySelector('[name=target_swolf]').value = t.target_swolf || '';
  });
  // Default club athletes list reload on change can be added if needed via AJAX
</script>
