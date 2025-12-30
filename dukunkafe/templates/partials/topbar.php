
<?php
// Load cafe settings for header branding (graceful if table missing during install)
$pdo_topbar = null; $settings_topbar = [];
try {
    $pdo_topbar = get_pdo();
    $stmt_tb = $pdo_topbar->query("SHOW TABLES LIKE 'settings'");
    if ($stmt_tb && $stmt_tb->fetchColumn()) {
        $settings_topbar = load_settings($pdo_topbar);
    }
} catch (Exception $e) {
    $settings_topbar = [];
}
?>

<div x-data="{ darkMode: false }" x-init="darkMode = (localStorage.getItem('theme')==='dark'); document.documentElement.classList.toggle('dark', darkMode)" class="no-print sticky top-0 z-10 flex-shrink-0 flex h-16 bg-brand-600 text-white shadow">
    <button @click="sidebarOpen = !sidebarOpen" class="px-4 border-r border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden">
        <span class="sr-only">Buka sidebar</span>
        <!-- Heroicon name: outline/menu-alt-2 -->
        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
        </svg>
    </button>
    <button @click="desktopSidebarOpen = !desktopSidebarOpen" class="px-4 border-r border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 hidden md:block">
        <span class="sr-only">Toggle sidebar</span>
        <i class="fa-solid fa-bars-staggered transition-transform duration-300" :class="{ 'rotate-180': !desktopSidebarOpen }"></i>
    </button>
    <div class="flex-1 px-4 flex justify-between">
        <!-- Store Header (Logo + Name) -->
        <div class="flex-1 flex items-center gap-3">
            <a href="<?= base_url('dashboard') ?>" class="flex items-center gap-2 text-lg font-bold text-white">
                <?php if (!empty($settings_topbar['cafe_logo'])): ?>
                    <img src="<?= htmlspecialchars(asset_url($settings_topbar['cafe_logo'])) ?>" alt="Logo" class="w-7 h-7 object-contain rounded" />
                <?php else: ?>
                    <i class="fa-solid fa-mug-hot text-white"></i>
                <?php endif; ?>
                <span><?= htmlspecialchars($settings_topbar['cafe_name'] ?? 'Dukun Kafe') ?></span>
            </a>
            <?php 
            // Branch selector (if branches table exists)
            $branches = [];
            try {
                if ($pdo_topbar) {
                    $has_br = $pdo_topbar->query("SHOW TABLES LIKE 'branches'")->fetchColumn();
                    if ($has_br) { $branches = $pdo_topbar->query("SELECT id, name FROM branches WHERE active = 1 ORDER BY name")->fetchAll(); }
                }
            } catch (Exception $e) {}
            if (!empty($branches)):
                $current_branch_id = get_current_branch_id();
            ?>
            <div x-data="{ open:false }" class="relative">
                <button @click="open=!open" class="px-3 py-1.5 rounded-full bg-white/10 text-white text-sm flex items-center gap-2">
                    <i class="fa-solid fa-code-branch"></i>
                    <span>
                        <?php foreach ($branches as $b) { if ((int)$b['id'] === $current_branch_id) { echo htmlspecialchars($b['name']); break; } }
                        if (empty($branches)) echo 'Cabang';
                        ?>
                    </span>
                    <i class="fa-solid fa-caret-down"></i>
                </button>
                <div x-show="open" @click.away="open=false" class="absolute mt-2 bg-white text-brand-900 rounded-xl shadow-card border border-brand-100 w-56 z-10">
                    <?php foreach ($branches as $b): ?>
                        <form method="POST" action="<?= htmlspecialchars(base_url('api/switch_branch')) ?>" class="border-b last:border-b-0">
                            <input type="hidden" name="branch_id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="w-full text-left px-3 py-2 hover:bg-brand-50 flex items-center justify-between">
                                <span><?= htmlspecialchars($b['name']) ?></span>
                                <?php if ((int)$b['id'] === $current_branch_id): ?><i class="fa-solid fa-check text-emerald-600"></i><?php endif; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="ml-4 flex items-center md:ml-6">
            <!-- Dark Mode Toggle -->
            <button @click="darkMode = !darkMode; document.documentElement.classList.toggle('dark', darkMode); try { localStorage.setItem('theme', darkMode?'dark':'light'); } catch(e){}" class="p-1 rounded-full text-white/80 hover:text-white focus:outline-none">
                <svg x-show="!darkMode" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                <svg x-show="darkMode" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-15.66l-.707.707M5.05 18.95l-.707.707M21 12h-1M4 12H3m15.66 8.66l-.707-.707M5.05 5.05l-.707-.707" /></svg>
            </button>

            <!-- Profile dropdown -->
            <div x-data="{ open: false }" class="ml-3 relative">
                <div>
                    <button @click="open = !open" type="button" class="max-w-xs bg-white dark:bg-gray-800 rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                        <span class="sr-only">Buka menu pengguna</span>
                        <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['name'] ?? 'User') ?>&background=random" alt="">
                    </button>
                </div>
                <div x-show="open" @click.away="open = false" x-transition class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200" role="menuitem" tabindex="-1">Profil Anda</a>
                    <a href="<?= base_url('logout') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200" role="menuitem" tabindex="-1">Keluar</a>
                </div>
            </div>
        </div>
    </div>
</div>




