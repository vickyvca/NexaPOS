<div class="max-w-xl bg-white border rounded p-4">
  <h1 class="text-xl font-semibold mb-3">Create Race</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label class="block mb-2">Meet
      <select class="w-full border rounded px-3 py-2" name="meet_id" required>
        <option value="">-- select --</option>
        <?php foreach ($meets as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?> (<?= h($m['meet_on']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <label>Relay
        <select class="w-full border rounded px-3 py-2" name="relay_type" id="relay_type">
          <option value="">-- None (Individual) --</option>
          <option value="4x50_free">4x50m Freestyle Relay</option>
          <option value="4x100_free">4x100m Freestyle Relay</option>
          <option value="4x200_free">4x200m Freestyle Relay</option>
          <option value="4x100_medley">4x100m Medley Relay</option>
        </select>
      </label>
      <label>Gender
        <select class="w-full border rounded px-3 py-2" name="gender" id="gender">
          <option value="X">All</option>
          <option value="M">Male</option>
          <option value="F">Female</option>
        </select>
      </label>
      <label>Stroke
        <select class="w-full border rounded px-3 py-2" name="stroke" id="stroke">
          <?php foreach (["FREE","BACK","BREAST","FLY","IM"] as $st): ?>
            <option value="<?= h($st) ?>"><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Distance (m)
        <select class="w-full border rounded px-3 py-2" name="distance_m" id="distance_m">
          <?php foreach ([50,100,200,400,800,1500] as $d): ?>
            <option value="<?= (int)$d ?>"><?= (int)$d ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
      <label>Age Group
        <select class="w-full border rounded px-3 py-2" name="age_group" id="age_group">
          <option value="">All</option>
          <?php foreach (["U12","U14","U16","U18","Open"] as $ag): ?>
            <option value="<?= h($ag) ?>"><?= h($ag) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Round<input class="w-full border rounded px-3 py-2" type="text" name="round_name" value="Final"></label>
      <label>Heat<input class="w-full border rounded px-3 py-2" type="number" name="heat_no" value="1" min="1"></label>
    </div>
    <div class="mt-4">
      <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Create</button>
    </div>
  </form>
</div>

<script>
  const relay = document.getElementById('relay_type');
  const stroke = document.getElementById('stroke');
  const dist = document.getElementById('distance_m');
  const gender = document.getElementById('gender');
  function toggleRelay() {
    const isRelay = relay.value !== '';
    stroke.disabled = isRelay;
    dist.disabled = isRelay;
    gender.disabled = isRelay;
    stroke.parentElement.classList.toggle('opacity-50', isRelay);
    dist.parentElement.classList.toggle('opacity-50', isRelay);
    gender.parentElement.classList.toggle('opacity-50', isRelay);
  }
  relay.addEventListener('change', toggleRelay);
  toggleRelay();
</script>
