
<?php

// File Konfigurasi Utama Aplikasi Dukun Cafe

return [
    // Konfigurasi Database Utama (MySQL/MariaDB)
    'database' => [
        'host' => '203.161.184.103',       // atau 'localhost'
        'port' => 3306,
        'dbname' => 'modecent_cafe',
        'user' => 'modecent_cafe',
        'password' => 'dukunkafe666',
        'charset' => 'utf8mb4'
    ],

    // Pengaturan Aplikasi
    'app' => [
        'name' => 'Dukun Cafe',
        'env' => 'production', // 'development' atau 'production'
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
