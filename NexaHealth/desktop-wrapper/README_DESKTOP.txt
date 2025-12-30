# Desktop Wrapper untuk NexaPOS

## Struktur
- desktop-wrapper/
  - start.bat          : Menjalankan PHP built-in server + buka aplikasi (WebView/browser mode app)
  - stop.bat           : Mematikan proses PHP server berdasarkan window title
  - php-portable/      : Letakkan PHP portable 8+ di sini (php.exe)
- NexaPOS/             : Folder aplikasi (config.php, pos, dll)

## Cara pakai
1. Unduh PHP portable (zip) versi 8+, extract ke `desktop-wrapper/php-portable/` sehingga ada `php.exe` di dalamnya.
2. Pastikan `config.php` terisi kredensial DB (MySQL/MariaDB). Jika pakai DB lokal portable, jalankan DB itu terlebih dulu.
3. Jalankan `start.bat` (double click). Akan:
   - Menyalakan PHP built-in server di 127.0.0.1:1818 dengan webroot `..\` (folder NexaPOS).
   - Membuka Edge/Chrome/Firefox dalam mode app ke `http://127.0.0.1:1818/pos/index.php`.
4. Untuk mematikan server, jalankan `stop.bat` atau tutup proses bernama "php-server" di Task Manager.

## Catatan
- Port default 1818; ubah di start.bat jika konflik.
- Jika ingin dibundel jadi installer/EXE, bisa bungkus start.bat dengan Inno Setup atau Bat-To-Exe.
- Lisensi/aktivasi belum diset; bisa ditambah belakangan di layer PHP.
