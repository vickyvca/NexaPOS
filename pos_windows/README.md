POS Windows (WPF) — Offline-only

Ringkasan
- Windows-only WPF (net8.0-windows), offline storage JSON file (tanpa DB eksternal)
- USB RAW ESC/POS printing via Winspool (tanpa dependency pihak ketiga)
- Modul awal: Login, POS, Inventory (set stok), Produk, Printer Settings

Struktur
- POS.App (WPF UI)
- POS.Core (model domain)
- POS.Data (JsonDatabase: simpan ke %LocalAppData%\POSWindows\pos.json)
- POS.Printing (RawPrinter: kirim ESC/POS RAW ke queue printer Windows)

Menjalankan
1) Buka Visual Studio 2022 (Workload: Desktop development with C++) atau .NET desktop
2) File → Open → Project/Solution → pilih `pos_windows/POS.App/POS.App.csproj`
3) Set startup project ke POS.App, lalu Run (F5)

Login
- Username: admin
- Password: admin

Pengaturan Printer
- Menu Printer → pilih printer Windows (USB) → pilih 58mm/80mm → Simpan → Tes Print

Catatan
- Penyimpanan JSON cocok untuk prototipe offline. Untuk produksi, ganti ke SQLite/EF Core.
