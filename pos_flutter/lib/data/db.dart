import 'dart:io';

import 'package:path_provider/path_provider.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

class AppDatabase {
  static Database? _db;

  static Future<void> bootstrap() async {
    if (Platform.isWindows) {
      sqfliteFfiInit();
      databaseFactory = databaseFactoryFfi;
    }
    await instance();
  }

  static Future<Database> instance() async {
    if (_db != null) return _db!;

    final Directory dir = await getApplicationSupportDirectory();
    await dir.create(recursive: true);
    final path = '${dir.path}${Platform.pathSeparator}pos.db';

    _db = await openDatabase(
      path,
      version: 1,
      onCreate: (db, version) async => _migrateV1(db),
      onUpgrade: (db, oldVersion, newVersion) async {
        // Future migrations
      },
    );
    await _seedDefaults(_db!);
    return _db!;
  }

  static Future<void> _migrateV1(Database db) async {
    // Core master tables
    await db.execute('''
      CREATE TABLE users (
        id TEXT PRIMARY KEY,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL,
        active INTEGER NOT NULL DEFAULT 1
      );
    ''');

    await db.execute('''
      CREATE TABLE warehouses (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        address TEXT
      );
    ''');

    await db.execute('''
      CREATE TABLE products (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        barcode TEXT,
        unit TEXT,
        sku TEXT,
        active INTEGER NOT NULL DEFAULT 1
      );
    ''');

    await db.execute('''
      CREATE TABLE product_prices (
        product_id TEXT NOT NULL,
        level TEXT NOT NULL, -- H1, H2, GROSIR
        price INTEGER NOT NULL,
        PRIMARY KEY(product_id, level),
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
      );
    ''');

    await db.execute('''
      CREATE TABLE product_stocks (
        product_id TEXT NOT NULL,
        warehouse_id TEXT NOT NULL,
        qty INTEGER NOT NULL DEFAULT 0,
        PRIMARY KEY(product_id, warehouse_id),
        FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
      );
    ''');

    await db.execute('''
      CREATE TABLE stock_moves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id TEXT NOT NULL,
        warehouse_id TEXT NOT NULL,
        qty_change INTEGER NOT NULL,
        reason TEXT NOT NULL,
        ref_id TEXT,
        created_at TEXT NOT NULL,
        FOREIGN KEY(product_id) REFERENCES products(id),
        FOREIGN KEY(warehouse_id) REFERENCES warehouses(id)
      );
    ''');

    await db.execute('''
      CREATE TABLE customers (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        phone TEXT,
        member_code TEXT
      );
    ''');

    await db.execute('''
      CREATE TABLE suppliers (
        id TEXT PRIMARY KEY,
        name TEXT NOT NULL,
        phone TEXT
      );
    ''');

    // Sales (simplified)
    await db.execute('''
      CREATE TABLE sales_invoices (
        id TEXT PRIMARY KEY,
        number TEXT UNIQUE,
        date TEXT NOT NULL,
        customer_id TEXT,
        subtotal INTEGER NOT NULL,
        discount INTEGER NOT NULL DEFAULT 0,
        tax_ppn INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        payment_status TEXT NOT NULL, -- PAID / PARTIAL / UNPAID
        warehouse_id TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        created_at TEXT NOT NULL,
        FOREIGN KEY(customer_id) REFERENCES customers(id),
        FOREIGN KEY(warehouse_id) REFERENCES warehouses(id)
      );
    ''');

    await db.execute('''
      CREATE TABLE sales_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id TEXT NOT NULL,
        product_id TEXT NOT NULL,
        qty INTEGER NOT NULL,
        price INTEGER NOT NULL,
        discount INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        FOREIGN KEY(invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
        FOREIGN KEY(product_id) REFERENCES products(id)
      );
    ''');

    // Purchases (simplified)
    await db.execute('''
      CREATE TABLE purchase_invoices (
        id TEXT PRIMARY KEY,
        number TEXT UNIQUE,
        date TEXT NOT NULL,
        supplier_id TEXT NOT NULL,
        subtotal INTEGER NOT NULL,
        discount INTEGER NOT NULL DEFAULT 0,
        tax_ppn INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        payment_status TEXT NOT NULL,
        warehouse_id TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        created_at TEXT NOT NULL,
        FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
        FOREIGN KEY(warehouse_id) REFERENCES warehouses(id)
      );
    ''');

    await db.execute('''
      CREATE TABLE purchase_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id TEXT NOT NULL,
        product_id TEXT NOT NULL,
        qty INTEGER NOT NULL,
        price INTEGER NOT NULL,
        discount INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        FOREIGN KEY(invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE,
        FOREIGN KEY(product_id) REFERENCES products(id)
      );
    ''');

    await db.execute('''
      CREATE TABLE app_settings (
        key TEXT PRIMARY KEY,
        value TEXT
      );
    ''');
  }

  static Future<void> _seedDefaults(Database db) async {
    // Seed default warehouse
    final w = await db.query('warehouses', limit: 1);
    if (w.isEmpty) {
      await db.insert('warehouses', {
        'id': 'GUDANG-UTAMA',
        'name': 'Gudang Utama',
        'address': null,
      });
    }
    // Seed admin user (password: admin)
    final u = await db.query('users', limit: 1);
    if (u.isEmpty) {
      await db.insert('users', {
        'id': 'ADMIN',
        'username': 'admin',
        'password_hash': 'admin',
        'role': 'admin',
        'active': 1,
      });
    }
  }
}
