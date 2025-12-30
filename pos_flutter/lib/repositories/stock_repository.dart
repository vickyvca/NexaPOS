import 'package:sqflite/sqflite.dart';

import '../data/db.dart';

class StockRepository {
  Future<Database> get _db async => AppDatabase.instance();

  Future<void> setStock({required String productId, required String warehouseId, required int qty, String reason = 'ADJUST'}) async {
    final db = await _db;
    await db.transaction((txn) async {
      final current = await txn.query('product_stocks', where: 'product_id=? AND warehouse_id=?', whereArgs: [productId, warehouseId], limit: 1);
      final prev = current.isEmpty ? 0 : (current.first['qty'] as int);
      final change = qty - prev;
      if (current.isEmpty) {
        await txn.insert('product_stocks', {'product_id': productId, 'warehouse_id': warehouseId, 'qty': qty});
      } else {
        await txn.update('product_stocks', {'qty': qty}, where: 'product_id=? AND warehouse_id=?', whereArgs: [productId, warehouseId]);
      }
      await txn.insert('stock_moves', {
        'product_id': productId,
        'warehouse_id': warehouseId,
        'qty_change': change,
        'reason': reason,
        'ref_id': null,
        'created_at': DateTime.now().toIso8601String(),
      });
    });
  }

  Future<List<Map<String, Object?>>> listStocks(String warehouseId, {String? query}) async {
    final db = await _db;
    final like = query != null && query.isNotEmpty ? '%$query%' : null;
    final where = like != null ? 'WHERE p.name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?' : '';
    final args = like != null ? [like, like, like, warehouseId] : [warehouseId];
    return db.rawQuery('''
      SELECT p.id, p.name, p.barcode, p.sku, IFNULL(s.qty,0) as qty
      FROM products p
      LEFT JOIN product_stocks s ON s.product_id=p.id AND s.warehouse_id=?
      $where
      ORDER BY p.name
    ''', like != null ? [warehouseId, ...args.take(3)] : [warehouseId]);
  }
}
