import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/pos/pos_page.dart';
import 'features/products/products_page.dart';
import 'features/inventory/inventory_page.dart';
import 'features/settings/printer_settings_page.dart';
import 'features/shell/home_shell.dart';
import 'features/auth/login_page.dart';
import 'state/providers.dart';

GoRouter createRouter() {
  return GoRouter(
    initialLocation: '/login',
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginPage(),
      ),
      ShellRoute(
        builder: (context, state, child) => HomeShell(child: child),
        routes: [
          GoRoute(
            path: '/pos',
            builder: (context, state) => const POSPage(),
            redirect: (context, state) => _guard(context),
          ),
          GoRoute(
            path: '/products',
            builder: (context, state) => const ProductsPage(),
            redirect: (context, state) => _guard(context),
          ),
          GoRoute(
            path: '/inventory',
            builder: (context, state) => const InventoryPage(),
            redirect: (context, state) => _guard(context),
          ),
          GoRoute(
            path: '/settings/printer',
            builder: (context, state) => const PrinterSettingsPage(),
            redirect: (context, state) => _guard(context),
          ),
        ],
      ),
    ],
  );
}

String? _guard(BuildContext context) {
  final user = ProviderScope.containerOf(context, listen: false).read(authProvider);
  return user == null ? '/login' : null;
}
