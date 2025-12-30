import 'package:sqflite/sqflite.dart';

import '../data/db.dart';

class SalesRepository {
  Future<Database> get _db async => AppDatabase.instance();

  Future<String> createSale({
    required String id,
    required String warehouseId,
    required List<SalesItemInput> items,
    int discount = 0,
    int taxPpn = 0,
    String? customerId,
  }) async {
    final db = await _db;
    return db.transaction((txn) async {
      final subtotal = items.fold<int>(0, (s, e) => s + e.price * e.qty - e.discount);
      final total = subtotal + taxPpn - discount;
      final now = DateTime.now().toIso8601String();
      final number = await _newNumber(txn);

      await txn.insert('sales_invoices', {
        'id': id,
        'number': number,
        'date': now,
        'customer_id': customerId,
        'subtotal': subtotal,
        'discount': discount,
        'tax_ppn': taxPpn,
        'total': total,
        'payment_status': 'PAID',
        'warehouse_id': warehouseId,
        'updated_at': now,
        'created_at': now,
      });

      for (final it in items) {
        await txn.insert('sales_items', {
          'invoice_id': id,
          'product_id': it.productId,
          'qty': it.qty,
          'price': it.price,
          'discount': it.discount,
          'total': it.qty * it.price - it.discount,
        });
        // stock -
        await _applyStockChange(txn, it.productId, warehouseId, -it.qty, 'SALE', id);
      }
      return number;
    });
  }

  Future<void> _applyStockChange(Transaction txn, String productId, String warehouseId, int qtyChange, String reason, String refId) async {
    await txn.insert('stock_moves', {
      'product_id': productId,
      'warehouse_id': warehouseId,
      'qty_change': qtyChange,
      'reason': reason,
      'ref_id': refId,
      'created_at': DateTime.now().toIso8601String(),
    });
    final existing = await txn.query('product_stocks', where: 'product_id=? AND warehouse_id=?', whereArgs: [productId, warehouseId], limit: 1);
    if (existing.isEmpty) {
      await txn.insert('product_stocks', {
        'product_id': productId,
        'warehouse_id': warehouseId,
        'qty': qtyChange,
      });
    } else {
      final qty = (existing.first['qty'] as int) + qtyChange;
      await txn.update('product_stocks', {'qty': qty}, where: 'product_id=? AND warehouse_id=?', whereArgs: [productId, warehouseId]);
    }
  }

  Future<String> _newNumber(Transaction txn) async {
    // Simple increment: SI-YYYYMMDD-XXXX
    final today = DateTime.now();
    final prefix = 'SI-${today.year}${today.month.toString().padLeft(2, '0')}${today.day.toString().padLeft(2, '0')}-';
    final rows = await txn.rawQuery("SELECT number FROM sales_invoices WHERE number LIKE ? ORDER BY number DESC LIMIT 1", ['$prefix%']);
    int seq = 1;
    if (rows.isNotEmpty) {
      final last = rows.first['number'] as String;
      final n = int.tryParse(last.substring(prefix.length)) ?? 0;
      seq = n + 1;
    }
    return '$prefix${seq.toString().padLeft(4, '0')}';
  }
}

class SalesItemInput {
  final String productId;
  final int qty;
  final int price;
  final int discount;
  SalesItemInput({required this.productId, required this.qty, required this.price, this.discount = 0});
}
