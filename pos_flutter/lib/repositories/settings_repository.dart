import 'package:sqflite/sqflite.dart';

import '../data/db.dart';

class SettingsRepository {
  Future<Database> get _db async => AppDatabase.instance();

  Future<void> setValue(String key, String value) async {
    final db = await _db;
    await db.insert('app_settings', {'key': key, 'value': value}, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<String?> getValue(String key) async {
    final db = await _db;
    final rows = await db.query('app_settings', where: 'key=?', whereArgs: [key], limit: 1);
    if (rows.isEmpty) return null;
    return rows.first['value'] as String?;
  }
}
