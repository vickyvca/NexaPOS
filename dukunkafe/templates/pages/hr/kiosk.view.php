
<!DOCTYPE html>
<html lang="en" class="h-full bg-brand-50 text-brand-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dukun Cafe' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { brand: { 50:'#eef7f0',100:'#d9efe0',200:'#b6dfc1',300:'#86c897',400:'#58b072',500:'#2f9452',600:'#237543',700:'#1c5b37',800:'#16472d',900:'#123a26' } }, boxShadow: { card: '0 6px 20px rgba(0,0,0,0.06)' }, borderRadius: { xxl: '1.5rem' } } } }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex items-center justify-center">

<div class="w-full max-w-md mx-auto bg-white rounded-xxl shadow-card border border-brand-100 p-8" x-data="{
    pin: '',
    message: <?= json_encode($message) ?>,
    message_type: <?= json_encode($message_type) ?>,
    add(digit) {
        if (this.pin.length < 6) {
            this.pin += digit;
        }
    },
    del() {
        this.pin = this.pin.slice(0, -1);
    },
    clear() {
        this.pin = '';
    },
    submit(type) {
        if (this.pin.length >= 4) {
            this.$refs.form.querySelector('#form_pin').value = this.pin;
            this.$refs.form.querySelector('#form_type').value = type;
            this.$refs.form.submit();
        }
    }
}">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-brand-800">Attendance Kiosk</h1>
        <p class="text-brand-700"><?= date('l, d F Y') ?></p>
        <p class="text-2xl font-mono text-brand-800" x-text="new Date().toLocaleTimeString()"></p>
    </div>

    <!-- PIN Display -->
    <div class="w-full h-16 bg-brand-50 border border-brand-100 rounded-xl flex items-center justify-center mb-4">
        <p class="text-3xl font-mono tracking-widest text-brand-900" x-text="'*'.repeat(pin.length)"></p>
    </div>

    <!-- Message Area -->
    <?php if ($message): ?>
    <div 
        class="p-4 mb-4 text-sm rounded-lg "
        :class="{
            'bg-green-100 text-green-700': message_type === 'success',
            'bg-red-100 text-red-700': message_type === 'error',
            'bg-blue-100 text-blue-700': message_type === 'info',
        }" 
        role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Numpad -->
    <div class="grid grid-cols-3 gap-3">
        <template x-for="i in [1, 2, 3, 4, 5, 6, 7, 8, 9]">
            <button @click="add(i)" class="py-4 bg-white border border-brand-200 text-2xl font-bold rounded-xl hover:bg-brand-50 focus:outline-none"> <span x-text="i"></span></button>
        </template>
        <button @click="del()" class="py-4 bg-amber-400 text-white text-2xl font-bold rounded-xl hover:bg-amber-500 focus:outline-none">DEL</button>
        <button @click="add(0)" class="py-4 bg-white border border-brand-200 text-2xl font-bold rounded-xl hover:bg-brand-50 focus:outline-none">0</button>
        <button @click="clear()" class="py-4 bg-red-600 text-white text-2xl font-bold rounded-xl hover:bg-red-700 focus:outline-none">C</button>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4 mt-6">
        <button @click="submit('IN')" class="p-4 bg-brand-600 text-white text-xl font-bold rounded-xl hover:bg-brand-700 focus:outline-none">Clock In</button>
        <button @click="submit('OUT')" class="p-4 bg-emerald-600 text-white text-xl font-bold rounded-xl hover:bg-emerald-700 focus:outline-none">Clock Out</button>
    </div>

    <!-- Hidden form for submission -->
    <form x-ref="form" method="POST" action="<?= base_url('hr/kiosk') ?>" class="hidden">
        <input type="hidden" name="pin" id="form_pin">
        <input type="hidden" name="type" id="form_type">
    </form>

    <div class="text-center mt-6">
        <a href="<?= base_url('login') ?>" class="text-sm text-brand-700 hover:underline">Login to Admin Panel</a>
    </div>

    <script>
        setInterval(() => {
            const el = document.querySelector('[x-text="new Date().toLocaleTimeString()"]');
            if (el) el.textContent = new Date().toLocaleTimeString();
        }, 1000);
    </script>
</div>

</body>
</html>
