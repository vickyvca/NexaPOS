import 'package:sqflite/sqflite.dart';
import '../data/db.dart';
import '../models/item.dart';
import '../models/sale.dart';
import '../models/supplier.dart';
import '../models/category.dart';

class Repository {
  final Database db = AppDatabase.instance.db;

  // Items
  Future<List<Item>> getItems({String? keyword}) async {
    final where = (keyword != null && keyword.isNotEmpty) ? "WHERE name LIKE ? OR code LIKE ?" : "";
    final args = (keyword != null && keyword.isNotEmpty) ? ['%$keyword%', '%$keyword%'] : [];
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

  Future<List<Supplier>> getSuppliers() async {
    final res = await db.rawQuery("SELECT * FROM suppliers ORDER BY name");
    return res.map(Supplier.fromMap).toList();
  }

  Future<int> upsertSupplier(Supplier s) async {
    if (s.id == null) {
      return await db.insert('suppliers', s.toMap());
    } else {
      await db.update('suppliers', s.toMap(), where: 'id=?', whereArgs: [s.id]);
      return s.id!;
    }
  }

  Future<List<Category>> getCategories() async {
    final res = await db.rawQuery("SELECT * FROM categories ORDER BY name");
    return res.map(Category.fromMap).toList();
  }

  Future<int> upsertCategory(Category c) async {
    if (c.id == null) {
      return await db.insert('categories', c.toMap());
    } else {
      await db.update('categories', c.toMap(), where: 'id=?', whereArgs: [c.id]);
      return c.id!;
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

  Future<List<Map<String, Object?>>> purchaseReport(String from, String to) async {
    return await db.rawQuery("SELECT date, SUM(total) as total FROM purchases WHERE date BETWEEN ? AND ? GROUP BY date ORDER BY date", [from, to]);
  }

  Future<List<Map<String, Object?>>> topItems({int limit = 5}) async {
    return await db.rawQuery("""
      SELECT i.name, SUM(si.qty) qty, SUM(si.subtotal) total
      FROM sale_items si
      JOIN items i ON i.id = si.item_id
      GROUP BY si.item_id
      ORDER BY total DESC
      LIMIT ?
    """, [limit]);
  }

  Future<Map<String, Object?>?> getSale(int id) async {
    final res = await db.rawQuery("SELECT * FROM sales WHERE id=?", [id]);
    if (res.isEmpty) return null;
    return res.first;
  }

  Future<List<Map<String, Object?>>> getSaleItems(int saleId) async {
    return await db.rawQuery("""
      SELECT si.*, i.name FROM sale_items si
      JOIN items i ON i.id=si.item_id
      WHERE sale_id=?
    """, [saleId]);
  }

  Future<int> getItemCount() async {
    final res = await db.rawQuery("SELECT COUNT(*) as c FROM items");
    return (res.first['c'] as int?) ?? 0;
  }

  Future<int> getLowStockCount() async {
    final res = await db.rawQuery("SELECT COUNT(*) as c FROM items WHERE stock <= min_stock AND min_stock > 0");
    return (res.first['c'] as int?) ?? 0;
  }

  Future<int> getTodaySalesTotal() async {
    final today = DateTime.now().toIso8601String().substring(0,10);
    final res = await db.rawQuery("SELECT SUM(grand_total) as t FROM sales WHERE date=?", [today]);
    return (res.first['t'] as int?) ?? 0;
  }

  Future<int> labaRugi(String from, String to) async {
    final revRow = await db.rawQuery("SELECT SUM(grand_total) as t FROM sales WHERE date BETWEEN ? AND ?", [from, to]);
    final revenue = (revRow.first['t'] as int?) ?? 0;
    final cogsRow = await db.rawQuery("""
      SELECT SUM(si.qty * i.buy_price) as cogs
      FROM sale_items si
      JOIN sales s ON s.id=si.sale_id
      JOIN items i ON i.id=si.item_id
      WHERE s.date BETWEEN ? AND ?
    """, [from, to]);
    final cogs = (cogsRow.first['cogs'] as int?) ?? 0;
    // Pembelian dijadikan beban opsional
    final purchRow = await db.rawQuery("SELECT SUM(total) as p FROM purchases WHERE date BETWEEN ? AND ? AND status='posted'", [from, to]);
    final expense = (purchRow.first['p'] as int?) ?? 0;
    return revenue - cogs - expense;
  }

  Future<String?> getSetting(String key) async {
    final res = await db.query('settings', where: 'key=?', whereArgs: [key]);
    if (res.isEmpty) return null;
    return res.first['value'] as String?;
  }

  Future<void> setSetting(String key, String value) async {
    await db.insert('settings', {'key': key, 'value': value}, conflictAlgorithm: ConflictAlgorithm.replace);
  }
}
