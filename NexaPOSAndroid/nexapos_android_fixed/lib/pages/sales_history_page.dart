import 'package:flutter/material.dart';
import '../services/repository.dart';

class SalesHistoryPage extends StatefulWidget {
  const SalesHistoryPage({super.key});

  @override
  State<SalesHistoryPage> createState() => _SalesHistoryPageState();
}

class _SalesHistoryPageState extends State<SalesHistoryPage> {
  List<Map<String, Object?>> rows = [];
  bool loading = true;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() => loading = true);
    rows = await Repository().db.rawQuery("SELECT id, sale_no, date, grand_total FROM sales ORDER BY id DESC LIMIT 100");
    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Riwayat Penjualan')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: rows.length,
              itemBuilder: (ctx, i) {
                final r = rows[i];
                return Card(
                  color: const Color(0xFF111827),
                  child: ListTile(
                    title: Text('${r['sale_no']}'),
                    subtitle: Text('${r['date']}'),
                    trailing: Text(_rp(r['grand_total'] ?? 0)),
                  ),
                );
              },
            ),
    );
  }
}

String _rp(Object? n){
  final v = (n as num?) ?? 0;
  return 'Rp ${v.toStringAsFixed(0)}';
}
