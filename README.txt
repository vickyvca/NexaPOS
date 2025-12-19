# NexaPOS - PHP Native POS

1. Buat database: `CREATE DATABASE nexapos CHARACTER SET utf8mb4;`
2. Import `database.sql` (schema + admin default).
3. Edit kredensial di `config.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS, BASE_URL).
4. Pastikan folder `logs/` writable dan file `logs/app.log` ada.
5. Taruh folder ini di `htdocs/NexaPOS` (atau sesuaikan BASE_URL).
6. Akses `http://localhost/NexaPOS/auth/login.php`
   - username: admin
   - password: admin123
7. Isi master data (kategori, barang, supplier, user) lalu gunakan menu POS atau Pembelian.

Fitur tambahan:
- Dark mode UI + ikon.
- Multi level harga (Lv1/Lv2/Lv3) + diskon persen default per barang.
- Validasi stok tidak boleh minus saat checkout.
- Dashboard KPI + grafik 7 hari.
- Backup/restore data SQL sederhana di menu Backup (admin).
