
<?php

// File Konfigurasi Utama Aplikasi Dukun Cafe

return [
    // Konfigurasi Database Utama (MySQL/MariaDB)
    'database' => [
        'host' => '127.0.0.1',       // atau 'localhost'
        'port' => 3306,
        'dbname' => 'dukun_cafe_db',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],

    // Pengaturan Aplikasi
    'app' => [
        'name' => 'Dukun Cafe',
        'env' => 'development', // 'development' atau 'production'
        'url' => 'http://localhost/dukunkafe/public',
    ],
    
    // (Contoh) Konfigurasi untuk koneksi kedua ke SQL Server
    // Tidak digunakan secara default, hanya sebagai contoh.
    'database_sqlsrv_example' => [
        'host' => 'your_sql_server_host',
        'port' => 1433,
        'dbname' => 'your_sqlsrv_db',
        'user' => 'your_sqlsrv_user',
        'password' => 'your_sqlsrv_password',
        'charset' => 'utf8'
    ],
];
