import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class HomeShell extends StatelessWidget {
  final Widget child;
  const HomeShell({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          NavigationRail(
            destinations: const [
              NavigationRailDestination(icon: Icon(Icons.point_of_sale), label: Text('POS')),
              NavigationRailDestination(icon: Icon(Icons.inventory), label: Text('Inventory')),
              NavigationRailDestination(icon: Icon(Icons.list), label: Text('Produk')),
              NavigationRailDestination(icon: Icon(Icons.print), label: Text('Printer')),
            ],
            selectedIndex: _idx(context),
            onDestinationSelected: (i) {
              switch (i) {
                case 0:
                  context.go('/pos');
                  break;
                case 1:
                  context.go('/inventory');
                  break;
                case 2:
                  context.go('/products');
                  break;
                case 3:
                  context.go('/settings/printer');
                  break;
              }
            },
          ),
          const VerticalDivider(width: 1),
          Expanded(child: child),
        ],
      ),
    );
  }

  int _idx(BuildContext context) {
    final loc = GoRouterState.of(context).uri.toString();
    if (loc.startsWith('/inventory')) return 1;
    if (loc.startsWith('/products')) return 2;
    if (loc.startsWith('/settings')) return 3;
    return 0;
  }
}
