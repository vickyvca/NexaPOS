import 'dart:convert';
import 'package:crypto/crypto.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../data/db.dart';

class AuthUser {
  final int id;
  final String name;
  final String username;
  final String role;
  AuthUser({required this.id, required this.name, required this.username, required this.role});
}

class AuthService {
  final db = AppDatabase.instance.db;

  String hash(String pwd) {
    return sha256.convert(utf8.encode(pwd)).toString();
  }

  Future<AuthUser?> login(String username, String password) async {
    final res = await db.rawQuery("SELECT * FROM users WHERE username=?", [username]);
    if (res.isEmpty) return null;
    final row = res.first;
    final stored = row['password_hash'] as String? ?? '';
    if (stored.isNotEmpty && stored != hash(password) && stored != password) {
      return null;
    }
    final user = AuthUser(
      id: row['id'] as int,
      name: row['name'] as String? ?? '',
      username: row['username'] as String? ?? '',
      role: row['role'] as String? ?? 'kasir',
    );
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('user_id', user.id);
    await prefs.setString('user_name', user.name);
    await prefs.setString('user_role', user.role);
    return user;
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user_id');
    await prefs.remove('user_name');
    await prefs.remove('user_role');
  }

  Future<AuthUser?> currentUser() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt('user_id');
    if (id == null) return null;
    return AuthUser(
      id: id,
      name: prefs.getString('user_name') ?? '',
      username: '',
      role: prefs.getString('user_role') ?? 'kasir',
    );
  }

  Future<void> changePassword(int userId, String newPassword) async {
    await db.update('users', {'password_hash': hash(newPassword)}, where: 'id=?', whereArgs: [userId]);
  }
}
