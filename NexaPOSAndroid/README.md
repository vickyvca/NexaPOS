# NexaPOSAndroid (Offline POS Android)

Opsi 2: build ulang jadi app Android native (Flutter) dengan database lokal SQLite (sqflite). Tanpa server/PC, semua data disimpan di device; backup/restore via file.

## Fitur target (offline)
- Master: barang, kategori, supplier, user (opsional), harga multi-level, stok minimum.
- Transaksi: penjualan POS (cart, diskon item/total, pajak), pembelian/stock-in, stock adjustment, stok per batch+expiry (opsional), cetak struk via Bluetooth ESC/POS.
- Laporan lokal: penjualan harian/bulanan/kasir, pembelian, stok menipis, kartu stok per barang.
- Backup/restore: ekspor database SQLite + CSV.

## Stack
- Flutter 3.x, Dart null-safety.
- State mgmt: Riverpod (simple) atau Provider. Contoh di sini pakai Riverpod.
- DB: sqflite + drift-like helper sendiri (raw SQL).
- Printing: `esc_pos_bluetooth` atau `blue_thermal_printer` (tambahkan saat perlu).

## Setup
```bash
cd NexaPOSAndroid
flutter pub get
flutter run
```

## Struktur
- `lib/main.dart` – bootstrap app + router.
- `lib/data/db.dart` – init SQLite, migrasi awal tabel master/transaksi.
- `lib/models/` – model data (Item, Purchase, Sale, CartItem).
- `lib/services/repository.dart` – CRUD dan query laporan.
- `lib/pages/` – UI: POS, pembelian, laporan, home.

## Catatan migrasi dari NexaPOS PHP
- Tidak ada PHP/MariaDB; semua logic dipindah ke Dart.
- Jika butuh sinkronisasi multi-device, tambahkan endpoint cloud / supabase nanti.
- Untuk batch/expiry, tabel `batches` disiapkan di migrasi awal.

## Build APK
```bash
flutter build apk --release
```
