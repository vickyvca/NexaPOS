<div class="mb-4">
  <h1 class="text-2xl font-bold">My Profile</h1>
  <div class="text-gray-600">Role: <?= h(current_user()['role']) ?></div>
</div>

<?php if (!empty($ok)): ?><div class="mb-3 text-green-700"><?= h($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="mb-3 text-red-600"><?= h($error) ?></div><?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-3">Account</h2>
    <div class="mb-2"><span class="text-gray-600">Name:</span> <?= h(current_user()['name']) ?></div>
    <div class="mb-2"><span class="text-gray-600">Email:</span> <?= h(current_user()['email']) ?></div>
  </div>

  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-3">Profile Details</h2>
    <?php if (in_array(current_user()['role'], ['athlete','admin'])): ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label class="block mb-2">Club
          <select class="w-full border rounded px-3 py-2" name="club_id">
            <option value="">-- None --</option>
            <?php foreach ($clubs as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ($athlete && (int)$athlete['club_id']===(int)$c['id'])?'selected':'' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label>Gender
            <select class="w-full border rounded px-3 py-2" name="gender">
              <?php $g = $athlete['gender'] ?? 'M'; ?>
              <option value="M" <?= $g==='M'?'selected':'' ?>>Male</option>
              <option value="F" <?= $g==='F'?'selected':'' ?>>Female</option>
            </select>
          </label>
          <label>Birthdate
            <input class="w-full border rounded px-3 py-2" type="date" name="birthdate" value="<?= h($athlete['birthdate'] ?? '2010-01-01') ?>">
          </label>
        </div>
        <div class="mt-4">
          <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Save Profile</button>
        </div>
      </form>
    <?php else: ?>
      <div class="text-gray-600">This role has no editable athlete profile. Contact organizer/admin if you need changes.</div>
    <?php endif; ?>
  </div>
</div>

