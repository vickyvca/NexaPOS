import 'package:flutter/material.dart';
import '../services/repository.dart';
import 'master_page.dart';
import 'pos_page.dart';
import 'purchase_page.dart';
import 'report_page.dart';
import 'sales_history_page.dart';
import 'settings_page.dart';
import '../services/auth_service.dart';
import 'login_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  int totalItems = 0;
  int lowStock = 0;
  int todaySales = 0;
  bool loading = true;
  List<Map<String, Object?>> topItems = [];

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final repo = Repository();
    totalItems = await repo.getItemCount();
    lowStock = await repo.getLowStockCount();
    todaySales = await repo.getTodaySalesTotal();
    topItems = await repo.topItems();
    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Dashboard')),
      drawer: _AppDrawer(),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: const [
                      _NavChip(label: 'POS', page: PosPage()),
                      _NavChip(label: 'Master', page: MasterPage()),
                      _NavChip(label: 'Pembelian', page: PurchasePage()),
                      _NavChip(label: 'Laporan', page: ReportPage()),
                      _NavChip(label: 'Riwayat', page: SalesHistoryPage()),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    children: [
                      _KpiCard(title: 'Item', value: '$totalItems'),
                      _KpiCard(title: 'Stok Menipis', value: '$lowStock', color: Colors.orange),
                      _KpiCard(title: 'Omzet Hari Ini', value: _rp(todaySales), color: Colors.green),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Text('Top Item', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Card(
                    color: const Color(0xFF111827),
                    child: Column(
                      children: topItems.map((t) => ListTile(
                        title: Text('${t['name']}'),
                        subtitle: Text('Qty: ${t['qty']}'),
                        trailing: Text(_rp((t['total'] as num?) ?? 0)),
                      )).toList(),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

class _KpiCard extends StatelessWidget {
  final String title;
  final String value;
  final Color? color;
  const _KpiCard({required this.title, required this.value, this.color});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 180,
      child: Card(
        color: color ?? const Color(0xFF111827),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 8),
              Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      ),
    );
  }
}

String _rp(num n) => 'Rp ${n.toStringAsFixed(0)}';

class _NavChip extends StatelessWidget {
  final String label;
  final Widget page;
  const _NavChip({required this.label, required this.page});

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text(label),
      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => page)),
    );
  }
}

class _AppDrawer extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: ListView(
        children: [
          const DrawerHeader(
            decoration: BoxDecoration(color: Colors.blue),
            child: Align(alignment: Alignment.bottomLeft, child: Text('NexaPOS Android', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
          ),
          ListTile(
            leading: const Icon(Icons.dashboard),
            title: const Text('Dashboard'),
            onTap: () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const DashboardPage())),
          ),
          ListTile(
            leading: const Icon(Icons.point_of_sale),
            title: const Text('POS'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PosPage())),
          ),
          ListTile(
            leading: const Icon(Icons.storage),
            title: const Text('Master'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MasterPage())),
          ),
          ListTile(
            leading: const Icon(Icons.shopping_cart),
            title: const Text('Pembelian'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PurchasePage())),
          ),
          ListTile(
            leading: const Icon(Icons.bar_chart),
            title: const Text('Laporan'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportPage())),
          ),
          ListTile(
            leading: const Icon(Icons.receipt_long),
            title: const Text('Riwayat'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SalesHistoryPage())),
          ),
          ListTile(
            leading: const Icon(Icons.settings),
            title: const Text('Pengaturan'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SettingsPage())),
          ),
          ListTile(
            leading: const Icon(Icons.logout),
            title: const Text('Logout'),
            onTap: () async {
              final svc = AuthService();
              await svc.logout();
              if (context.mounted) Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const LoginPage()), (r) => false);
            },
          ),
        ],
      ),
    );
  }
}
