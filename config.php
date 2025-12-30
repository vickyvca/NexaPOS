<?php
// Konfigurasi utama
define('DB_HOST', 'localhost');
define('DB_NAME', 'nexapos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/NexaPOS'); // sesuaikan jika folder berbeda
define('APP_NAME', 'NexaPOS');
date_default_timezone_set('Asia/Jakarta');
define('LOG_FILE', __DIR__ . '/logs/app.log');
define('LICENSE_FILE', __DIR__ . '/license.key');
define('LICENSE_SECRET', 'CHANGE_ME_SECRET'); // ubah ke secret acak
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);
?>
