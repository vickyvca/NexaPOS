<div class="max-w-md mx-auto bg-white border rounded p-6">
  <h1 class="text-xl font-bold mb-4">Register</h1>
  <?php if (!empty($ok)): ?><div class="text-green-700 mb-3"><?= h($ok) ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="text-red-600 mb-3"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <label class="block mb-2">Name
      <input class="w-full border rounded px-3 py-2" type="text" name="name" required>
    </label>
    <label class="block mb-2">Email
      <input class="w-full border rounded px-3 py-2" type="email" name="email" required>
    </label>
    <label class="block mb-4">Password
      <input class="w-full border rounded px-3 py-2" type="password" name="password" required>
    </label>
    <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Register</button>
    <a class="ml-3 text-blue-600" href="<?= h(BASE_URL) ?>/public/index.php?p=login">Back to Login</a>
  </form>
</div>

