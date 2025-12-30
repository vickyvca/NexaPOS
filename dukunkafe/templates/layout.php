
<!DOCTYPE html>
<html lang="en" class="h-full bg-brand-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dukun Cafe' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      try { if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); } } catch (e) {}
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#eef7f0',
                100: '#d9efe0',
                200: '#b6dfc1',
                300: '#86c897',
                400: '#58b072',
                500: '#2f9452', // primary green
                600: '#237543',
                700: '#1c5b37',
                800: '#16472d',
                900: '#123a26'
              }
            },
            boxShadow: {
              card: '0 6px 20px rgba(0,0,0,0.06)'
            },
            borderRadius: {
              xxl: '1.5rem'
            }
          }
        }
      }
    </script>
    <script>
      window.ASSET_BASE = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') ?>";
    </script>
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            .no-print, .no-print * {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact; /* Chrome, Safari */
                color-adjust: exact; /* Firefox */
            }
        }
        /* Global readability tweaks */
        html, body { color: #0f172a; text-rendering: optimizeLegibility; -webkit-font-smoothing: antialiased; }
        h1, h2, h3, h4 { color: #123a26; }
        /* Form baseline across pages */
        input[type="text"], input[type="number"], input[type="email"], input[type="password"], select, textarea {
            background-color: #ffffff;
            border: 1px solid #d9efe0; /* brand-100/200 */
            color: #0f172a;
            border-radius: 0.75rem; /* rounded-xl */
            padding: 0.625rem 0.75rem; /* px-3 py-2.5 */
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #58b072; /* brand-400 */
            box-shadow: 0 0 0 3px rgba(88, 176, 114, 0.25);
        }
        /* Table fallback (for pages not yet migrated) */
        table thead th {
            background-color: #eef7f0; /* brand-50 */
            color: #1c5b37; /* brand-700 */
            font-weight: 600;
        }
        table tbody tr + tr { border-top: 1px solid #d9efe0; }
    </style>
</head>
<body class="h-full">

<div x-data="{ sidebarOpen: false, desktopSidebarOpen: true }" class="h-screen flex overflow-hidden bg-brand-50">
    <!-- Off-canvas menu for mobile -->
    <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" x-cloak>
        <div @click="sidebarOpen = false" class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
            <?php include __DIR__ . '/partials/sidebar.php'; ?>
        </div>
    </div>

    <!-- Static sidebar for desktop -->
    <div x-show="desktopSidebarOpen" class="no-print hidden md:flex md:flex-shrink-0" x-cloak>
        <div class="flex flex-col w-64">
            <?php include __DIR__ . '/partials/sidebar.php'; ?>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex flex-col w-0 flex-1 overflow-hidden">
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <main class="flex-1 relative overflow-y-auto focus:outline-none">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-brand-800"><?= $title ?? '' ?></h1>
                        <?php 
                        if (isset($back_url)): 
                        ?>
                            <a href="<?= $back_url ?>" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded-full">
                                &larr; Kembali
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mt-4">
                    <!-- Main page content starts here -->
                    <div class="bg-white p-6 rounded-xxl shadow-card">
                        <?php require $viewPath; ?>
                    </div>
                    <!-- Main page content ends here -->
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
