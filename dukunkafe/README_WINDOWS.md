
# Dukun Cafe (POSRestoLite) - Panduan Setup di Windows (XAMPP)

Ini adalah panduan untuk menjalankan aplikasi Point of Sale "Dukun Cafe" di lingkungan Windows menggunakan XAMPP.

## Prasyarat

1.  **XAMPP Terinstal:** Pastikan Anda sudah menginstal XAMPP. Unduh dari [situs resmi Apache Friends](https://www.apachefriends.org/index.html).
2.  **PHP 8.2+:** Versi XAMPP yang Anda instal sebaiknya sudah menyertakan PHP versi 8.2 atau lebih baru.
3.  **Ekstensi PHP:** Pastikan ekstensi `pdo_mysql`, `mbstring`, dan `intl` aktif di file `php.ini` Anda. Biasanya sudah aktif secara default di XAMPP modern.

## Langkah-langkah Instalasi

1.  **Salin Folder Proyek:**
    *   Salin seluruh folder `dukunkafe` ini ke dalam folder `htdocs` di direktori instalasi XAMPP Anda.
    *   Contoh path: `C:\xampp\htdocs\dukunkafe`

2.  **Buat Database:**
    *   Jalankan **XAMPP Control Panel**, lalu start service **Apache** dan **MySQL**.
    *   Buka browser dan akses `http://localhost/phpmyadmin`.
    *   Buat database baru. Anda bisa menamainya `dukun_cafe_db`.

3.  **Impor Skema & Data:**
    *   Pilih database `dukun_cafe_db` yang baru saja Anda buat.
    *   Klik tab **"Import"**.
    *   Klik **"Choose File"** dan arahkan ke file `install.sql` yang ada di dalam folder proyek: `C:\xampp\htdocs\dukunkafe\deploy\install.sql`.
    *   Klik tombol **"Go"** atau **"Import"** di bagian bawah halaman untuk memulai proses impor. Tunggu hingga selesai.

4.  **Konfigurasi Koneksi Database:**
    *   Buka file `config.php` yang ada di `C:\xampp\htdocs\dukunkafe\config.php` menggunakan teks editor.
    *   Ubah nilai koneksi database sesuai dengan pengaturan Anda. Jika Anda mengikuti langkah 2, konfigurasinya akan seperti ini:
        ```php
        'db_host' => '127.0.0.1',      // atau 'localhost'
        'db_name' => 'dukun_cafe_db',
        'db_user' => 'root',
        'db_pass' => '' // Kosongkan jika password root MySQL Anda kosong
        ```

5.  **Akses Aplikasi:**
    *   Buka browser dan akses URL berikut: `http://localhost/dukunkafe/public`
    *   Halaman login akan muncul. Anda bisa login menggunakan akun demo:
        *   **Email:** `admin@demo.test`
        *   **Password:** `Admin123!`

## (Opsional) Menambah Koneksi SQL Server

Aplikasi ini dikonfigurasi untuk MySQL/MariaDB. Jika Anda perlu menambahkan koneksi kedua ke SQL Server di masa mendatang, Anda perlu:
1.  Mengunduh dan menginstal [Microsoft PHP Drivers for SQL Server](https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server).
2.  Mengaktifkan ekstensi `php_pdo_sqlsrv_xx_nts.dll` dan `php_sqlsrv_xx_nts.dll` di `php.ini`.
3.  Menambahkan konfigurasi koneksi kedua di `config.php` dan membuat helper function baru untuk koneksi tersebut.
