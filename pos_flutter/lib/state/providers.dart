import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../repositories/product_repository.dart';
import '../repositories/warehouse_repository.dart';
import '../repositories/sales_repository.dart';
import '../repositories/settings_repository.dart';

final productRepositoryProvider = Provider<ProductRepository>((ref) {
  return ProductRepository();
});

final warehouseRepositoryProvider = Provider<WarehouseRepository>((ref) => WarehouseRepository());
final salesRepositoryProvider = Provider<SalesRepository>((ref) => SalesRepository());
final settingsRepositoryProvider = Provider<SettingsRepository>((ref) => SettingsRepository());

// Simple auth state
class AuthState extends StateNotifier<String?> {
  AuthState() : super(null);
  void setUser(String? username) => state = username;
}

final authProvider = StateNotifierProvider<AuthState, String?>((ref) => AuthState());
