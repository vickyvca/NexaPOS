import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';

class AppDatabase {
  AppDatabase._();
  static final AppDatabase instance = AppDatabase._();
  Database? _db;

  Database get db {
    if (_db == null) throw Exception('DB not initialized');
    return _db!;
  }

  Future<void> init() async {
    final dir = await getApplicationDocumentsDirectory();
    final path = join(dir.path, 'nexapos_android.db');
    _db = await openDatabase(
      path,
      version: 1,
      onCreate: (db, version) async {
        // Master
        await db.execute('''CREATE TABLE items(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          code TEXT,
          barcode TEXT,
          name TEXT,
          category TEXT,
          unit TEXT,
          buy_price INTEGER,
          sell_price INTEGER,
          sell_price_lv2 INTEGER,
          sell_price_lv3 INTEGER,
          stock REAL DEFAULT 0,
          min_stock REAL DEFAULT 0,
          is_active INTEGER DEFAULT 1
        )''');
        await db.execute('''CREATE TABLE batches(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          item_id INTEGER,
          batch_no TEXT,
          expiry TEXT,
          stock REAL DEFAULT 0
        )''');
        await db.execute('''CREATE TABLE suppliers(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name TEXT,
          phone TEXT,
          address TEXT
        )''');

        // Pembelian
        await db.execute('''CREATE TABLE purchases(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          purchase_no TEXT,
          supplier_id INTEGER,
          date TEXT,
          total INTEGER,
          status TEXT
        )''');
        await db.execute('''CREATE TABLE purchase_items(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          purchase_id INTEGER,
          item_id INTEGER,
          batch_id INTEGER,
          batch_no TEXT,
          expiry TEXT,
          qty REAL,
          price INTEGER,
          subtotal INTEGER
        )''');

        // Penjualan
        await db.execute('''CREATE TABLE sales(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          sale_no TEXT,
          date TEXT,
          total INTEGER,
          discount INTEGER,
          grand_total INTEGER,
          payment_method TEXT,
          cash_paid INTEGER,
          change_amount INTEGER,
          customer_name TEXT
        )''');
        await db.execute('''CREATE TABLE sale_items(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          sale_id INTEGER,
          item_id INTEGER,
          batch_id INTEGER,
          batch_no TEXT,
          expiry TEXT,
          qty REAL,
          price INTEGER,
          discount INTEGER,
          subtotal INTEGER
        )''');

        // Stok moves
        await db.execute('''CREATE TABLE stock_moves(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          item_id INTEGER,
          batch_id INTEGER,
          date TEXT,
          qty_in REAL,
          qty_out REAL,
          note TEXT,
          ref_type TEXT,
          ref_id INTEGER
        )''');
      },
    );
  }
}
