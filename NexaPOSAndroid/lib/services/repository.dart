import 'package:sqflite/sqflite.dart';
import '../data/db.dart';
import '../models/item.dart';
import '../models/sale.dart';

class Repository {
  final Database db = AppDatabase.instance.db;

  // Items
  Future<List<Item>> getItems({String? keyword}) async {
    final where = (keyword != null && keyword.isNotEmpty) ? "WHERE name LIKE ?" : "";
    final args = (keyword != null && keyword.isNotEmpty) ? ['%$keyword%'] : [];
    final res = await db.rawQuery("SELECT * FROM items $where ORDER BY name LIMIT 200", args);
    return res.map(Item.fromMap).toList();
  }

  Future<int> upsertItem(Item item) async {
    if (item.id == null) {
      return await db.insert('items', item.toMap());
    } else {
      await db.update('items', item.toMap(), where: 'id=?', whereArgs: [item.id]);
      return item.id!;
    }
  }

  // Sales
  Future<int> saveSale(Sale sale) async {
    return await db.transaction((txn) async {
      final saleId = await txn.insert('sales', {
        'sale_no': sale.saleNo,
        'date': sale.date,
        'total': sale.total,
        'discount': sale.discount,
        'grand_total': sale.grandTotal,
        'payment_method': sale.paymentMethod,
        'cash_paid': sale.cashPaid,
        'change_amount': sale.changeAmount,
        'customer_name': sale.customerName
      });
      for (final item in sale.items) {
        await txn.insert('sale_items', {
          'sale_id': saleId,
          'item_id': item.id,
          'batch_id': item.batchId,
          'batch_no': item.batchNo,
          'expiry': item.expiry,
          'qty': item.qty,
          'price': item.price,
          'discount': item.discount,
          'subtotal': item.subtotal
        });
        await txn.rawUpdate("UPDATE items SET stock = stock - ? WHERE id=?", [item.qty, item.id]);
        if (item.batchId != null) {
          await txn.rawUpdate("UPDATE batches SET stock = stock - ? WHERE id=?", [item.qty, item.batchId]);
        }
        await txn.insert('stock_moves', {
          'item_id': item.id,
          'batch_id': item.batchId,
          'date': sale.date,
          'qty_in': 0,
          'qty_out': item.qty,
          'note': 'Penjualan ${sale.saleNo}',
          'ref_type': 'sale',
          'ref_id': saleId
        });
      }
      return saleId;
    });
  }

  Future<List<Map<String, Object?>>> salesReport(String from, String to) async {
    return await db.rawQuery("SELECT date, SUM(grand_total) as total FROM sales WHERE date BETWEEN ? AND ? GROUP BY date ORDER BY date", [from, to]);
  }
}
