import 'package:flutter/material.dart';
import '../services/repository.dart';

class ReportPage extends StatefulWidget {
  const ReportPage({super.key});

  @override
  State<ReportPage> createState() => _ReportPageState();
}

class _ReportPageState extends State<ReportPage> {
  final repo = Repository();
  List<Map<String, Object?>> data = [];
  DateTimeRange range = DateTimeRange(start: DateTime.now().subtract(const Duration(days: 7)), end: DateTime.now());

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final from = range.start.toIso8601String().substring(0,10);
    final to = range.end.toIso8601String().substring(0,10);
    data = await repo.salesReport(from, to);
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Laporan Penjualan')),
      body: Column(
        children: [
          ListTile(
            title: Text('Periode: ${range.start.toString().substring(0,10)} - ${range.end.toString().substring(0,10)}'),
            trailing: TextButton(
              onPressed: () async {
                final picked = await showDateRangePicker(
                  context: context,
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2100),
                  initialDateRange: range,
                );
                if (picked != null) {
                  range = picked;
                  await load();
                }
              },
              child: const Text('Ganti'),
            ),
          ),
          Expanded(
            child: ListView.builder(
              itemCount: data.length,
              itemBuilder: (ctx, i) {
                final row = data[i];
                return ListTile(
                  title: Text(row['date'] as String),
                  trailing: Text(_rp(row['total'] ?? 0)),
                );
              },
            ),
          )
        ],
      ),
    );
  }
}

String _rp(Object? n){
  final v = (n as num?) ?? 0;
  return 'Rp ${v.toStringAsFixed(0)}';
}
