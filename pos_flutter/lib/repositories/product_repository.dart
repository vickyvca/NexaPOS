import 'package:sqflite/sqflite.dart';

import '../data/db.dart';
import '../models/product.dart';

class ProductRepository {
  Future<Database> get _db async => AppDatabase.instance();

  Future<void> upsert(Product p, {int? priceH1, int? priceH2, int? priceGrosir}) async {
    final db = await _db;
    await db.insert(
      'products',
      {
        'id': p.id,
        'name': p.name,
        'barcode': p.barcode,
        'unit': p.unit,
        'sku': p.sku,
        'active': p.active ? 1 : 0,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );

    if (priceH1 != null) {
      await _upsertPrice(db, p.id, 'H1', priceH1);
    }
    if (priceH2 != null) {
      await _upsertPrice(db, p.id, 'H2', priceH2);
    }
    if (priceGrosir != null) {
      await _upsertPrice(db, p.id, 'GROSIR', priceGrosir);
    }
  }

  Future<void> _upsertPrice(Database db, String productId, String level, int price) async {
    await db.insert(
      'product_prices',
      {
        'product_id': productId,
        'level': level,
        'price': price,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<List<Map<String, Object?>>> list({String? query}) async {
    final db = await _db;
    if (query != null && query.isNotEmpty) {
      final like = '%$query%';
      return db.rawQuery('''
        SELECT p.*, 
          (SELECT price FROM product_prices WHERE product_id=p.id AND level='H1') AS price_h1,
          (SELECT price FROM product_prices WHERE product_id=p.id AND level='H2') AS price_h2,
          (SELECT price FROM product_prices WHERE product_id=p.id AND level='GROSIR') AS price_grosir
        FROM products p
        WHERE p.name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?
        ORDER BY p.name
      ''', [like, like, like]);
    }
    return db.rawQuery('''
      SELECT p.*, 
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='H1') AS price_h1,
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='H2') AS price_h2,
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='GROSIR') AS price_grosir
      FROM products p
      ORDER BY p.name
    ''');
  }

  Future<void> delete(String id) async {
    final db = await _db;
    await db.delete('products', where: 'id=?', whereArgs: [id]);
    await db.delete('product_prices', where: 'product_id=?', whereArgs: [id]);
  }

  Future<Map<String, Object?>?> findByCodeOrName(String q) async {
    final db = await _db;
    final like = '%$q%';
    final rows = await db.rawQuery('''
      SELECT p.*, 
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='H1') AS price_h1,
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='H2') AS price_h2,
        (SELECT price FROM product_prices WHERE product_id=p.id AND level='GROSIR') AS price_grosir
      FROM products p
      WHERE p.barcode = ? OR p.name LIKE ? OR p.sku LIKE ?
      ORDER BY CASE WHEN p.barcode = ? THEN 0 ELSE 1 END, p.name
      LIMIT 1
    ''', [q, like, like, q]);
    if (rows.isEmpty) return null;
    return rows.first;
  }
}
