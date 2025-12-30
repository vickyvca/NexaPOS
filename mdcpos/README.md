MDC POS PHP Endpoints

Prasyarat
- PHP 8.x dengan driver SQL Server (`sqlsrv` + `pdo_sqlsrv`) aktif.
- Akses ke SQL Server yang berisi schema tabel terkait (REF_JENIS, T_SUPLIER, T_BARANG, dsb.).
- Kredensial DB dapat diatur via environment variable: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.

Menjalankan (Development)
- Buka terminal di folder `mdcpos` lalu jalankan: `php -S 127.0.0.1:8010`.
- Akses endpoint secara langsung, misalnya: `http://127.0.0.1:8010/api_suppliers.php`.
- UI Dark Mode dengan Sidebar: buka `http://127.0.0.1:8010/ui/index.html`.

Endpoint Utama
- `GET /api_suppliers.php` — daftar supplier (T_SUPLIER).
- `GET /api_ref_jenis.php` — daftar jenis (REF_JENIS).
- `GET /api_ref_jenis.php?kode=01` — detail 1 jenis berdasarkan `KODEJN`.
- `POST /api_ref_jenis.php` — create jenis. Body JSON: `{ "KODEJN": "06", "KETERANGAN": "Aksesori Musiman" }`.
- `PUT /api_ref_jenis.php` — update jenis. Body JSON sama seperti POST.
- `DELETE /api_ref_jenis.php` — hapus jenis. Body JSON: `{ "KODEJN": "06" }`.
- `POST /api_master_barang.php` — create master barang + stok + HPP (lihat contoh payload di `# GET Supplier List.md`).

UI (Dark Mode + Sidebar)
- `ui/index.html` memuat layout utama (sidebar gelap) dan router berbasis hash.
- Views:
  - `dashboard`, `pos`, `products`, `jenis`, `suppliers`, `hutang` di `ui/views/`.
  - POS view menggunakan `api_products.php` dan `api_pos_sale.php` untuk checkout.

Contoh cURL
- `curl "http://127.0.0.1:8010/api_ref_jenis.php"`
- `curl "http://127.0.0.1:8010/api_ref_jenis.php?kode=01"`
- `curl -X POST "http://127.0.0.1:8010/api_ref_jenis.php" -H "Content-Type: application/json" -d "{\"KODEJN\":\"06\",\"KETERANGAN\":\"Aksesori Musiman\"}"`

Catatan
- Untuk koneksi ke server selain default, export environment variable sebelum menjalankan server, contoh (PowerShell):
  - `$env:DB_HOST = '192.168.1.10'`
  - `$env:DB_NAME = 'MODECENTRE2'`
  - `$env:DB_USER = 'sa'`
  - `$env:DB_PASSWORD = 'secret'`
- File `master_jenis.html` ditujukan untuk pengujian cepat via browser; sesuaikan `API_URL` ke host lokal jika perlu.
