import 'package:flutter/material.dart';
import '../services/repository.dart';
import '../models/item.dart';

class PurchasePage extends StatefulWidget {
  const PurchasePage({super.key});

  @override
  State<PurchasePage> createState() => _PurchasePageState();
}

class _PurchasePageState extends State<PurchasePage> {
  List<Item> items = [];
  final repo = Repository();
  bool loading = true;
  final TextEditingController qtyC = TextEditingController(text: '1');
  Item? selected;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() => loading = true);
    items = await repo.getItems();
    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pembelian/Stock In')),
      body: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            DropdownButtonFormField<Item>(
              initialValue: selected,
              items: items.map((e) => DropdownMenuItem(value: e, child: Text(e.name))).toList(),
              onChanged: (v) => setState(()=> selected = v),
              decoration: const InputDecoration(labelText: 'Barang'),
            ),
            TextField(
              controller: qtyC,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Qty'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: selected == null ? null : () async {
                final qty = double.tryParse(qtyC.text) ?? 0;
                if (qty <= 0) return;
                await repo.db.transaction((txn) async {
                  await txn.rawUpdate("UPDATE items SET stock = stock + ? WHERE id=?", [qty, selected!.id]);
                  await txn.insert('stock_moves', {
                    'item_id': selected!.id,
                    'date': DateTime.now().toIso8601String().substring(0,10),
                    'qty_in': qty,
                    'qty_out': 0,
                    'note': 'Manual In',
                    'ref_type': 'adjust',
                    'ref_id': null
                  });
                });
                if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Stok bertambah')));
              },
              child: const Text('Simpan'),
            ),
            if (loading) const Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()),
          ],
        ),
      ),
    );
  }
}
