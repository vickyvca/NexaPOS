import 'package:flutter/material.dart';
import 'pos_page.dart';
import 'purchase_page.dart';
import 'report_page.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('NexaPOS Android')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: GridView.count(
          crossAxisCount: 2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          children: [
            _MenuCard(
              icon: Icons.point_of_sale,
              title: 'POS',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PosPage())),
            ),
            _MenuCard(
              icon: Icons.shopping_cart,
              title: 'Pembelian',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PurchasePage())),
            ),
            _MenuCard(
              icon: Icons.bar_chart,
              title: 'Laporan',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportPage())),
            ),
          ],
        ),
      ),
    );
  }
}

class _MenuCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;
  const _MenuCard({required this.icon, required this.title, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 42),
              const SizedBox(height: 8),
              Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      ),
    );
  }
}
