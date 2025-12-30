POS Flutter (Windows + Android)

Ringkasan
- Offline-first menggunakan SQLite (sqflite / sqflite_ffi)
- Thermal printing: Bluetooth (Android) dan LAN (Windows/Android), USB Windows disiapkan stub
- Harga bertingkat (H1, H2, Grosir), PPN opsional

Struktur
- lib/
  - app.dart (router)
  - main.dart (bootstrap)
  - data/ (database + migrasi)
  - models/ (entitas)
  - repositories/ (akses data)
  - services/printing/ (abstraksi printer dan backend)
  - features/ (UI: POS, Produk, Settings)

Menjalankan (setelah Flutter terpasang)
1) flutter pub get
2) flutter run -d windows  (atau -d android)

Catatan Printing
- Android Bluetooth: plugin blue_thermal_printer
- LAN: esc_pos_printer (TCP) – rekomendasi untuk Windows
- USB Windows: belum diaktifkan (butuh binding khusus). Disediakan antarmuka untuk implementasi lanjutan.
