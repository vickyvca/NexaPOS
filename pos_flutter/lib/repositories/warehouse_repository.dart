import 'package:sqflite/sqflite.dart';

import '../data/db.dart';

class WarehouseRepository {
  Future<Database> get _db async => AppDatabase.instance();

  Future<List<Map<String, Object?>>> list() async {
    final db = await _db;
    return db.query('warehouses', orderBy: 'name');
  }
}
