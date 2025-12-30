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
  List<_PurchaseRow> rows = [ _PurchaseRow() ];

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
      body: loading ? const Center(child: CircularProgressIndicator()) : Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Expanded(
              child: ListView.builder(
                itemCount: rows.length,
                itemBuilder: (ctx, i) {
                  final row = rows[i];
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(8),
                      child: Column(
                        children: [
                          DropdownButtonFormField<int>(
                            initialValue: row.itemId,
                            items: items.map((e) => DropdownMenuItem(value: e.id, child: Text('${e.code} - ${e.name}'))).toList(),
                            onChanged: (val) {
                              setState(() {
                                row.itemId = val;
                                final it = items.firstWhere((element) => element.id == val, orElse: () => items.first);
                                row.price = it.buyPrice;
                              });
                            },
                            decoration: const InputDecoration(labelText: 'Barang'),
                          ),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: row.qtyC,
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(labelText: 'Qty'),
                                  onChanged: (_) => setState(()=>row.recalc()),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: TextField(
                                  controller: row.priceC,
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(labelText: 'Harga Beli'),
                                  onChanged: (_) => setState(()=>row.recalc()),
                                ),
                              ),
                            ],
                          ),
                          Align(
                            alignment: Alignment.centerRight,
                            child: Text('Subtotal: ${_rp(row.subtotal)}'),
                          ),
                          Align(
                            alignment: Alignment.centerRight,
                            child: IconButton(
                              icon: const Icon(Icons.delete),
                              onPressed: rows.length > 1 ? () => setState((){ rows.removeAt(i); }) : null,
                            ),
                          )
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            Row(
              children: [
                ElevatedButton.icon(
                  onPressed: () => setState(()=> rows.add(_PurchaseRow())),
                  icon: const Icon(Icons.add),
                  label: const Text('Tambah Baris'),
                ),
                const Spacer(),
                Text('Total: ${_rp(rows.fold<int>(0, (p, r) => p + r.subtotal))}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () async {
                  final total = rows.fold<int>(0, (p, r) => p + r.subtotal);
                  if (rows.any((r) => r.itemId == null || r.qty <= 0)) return;
                  final purchaseNo = 'PB${DateTime.now().millisecondsSinceEpoch}';
                  await repo.db.transaction((txn) async {
                    final pid = await txn.insert('purchases', {
                      'purchase_no': purchaseNo,
                      'supplier_id': null,
                      'date': DateTime.now().toIso8601String().substring(0,10),
                      'total': total,
                      'status': 'posted'
                    });
                    for (final r in rows) {
                      await txn.insert('purchase_items', {
                        'purchase_id': pid,
                        'item_id': r.itemId,
                        'qty': r.qty,
                        'price': r.price,
                        'subtotal': r.subtotal,
                        'batch_no': null,
                        'expiry': null
                      });
                      await txn.rawUpdate("UPDATE items SET stock = stock + ?, buy_price=? WHERE id=?", [r.qty, r.price, r.itemId]);
                      await txn.insert('stock_moves', {
                        'item_id': r.itemId,
                        'batch_id': null,
                        'date': DateTime.now().toIso8601String().substring(0,10),
                        'qty_in': r.qty,
                        'qty_out': 0,
                        'note': 'Pembelian $purchaseNo',
                        'ref_type': 'purchase',
                        'ref_id': pid
                      });
                    }
                  });
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pembelian tersimpan')));
                  }
                },
                child: const Text('Simpan Pembelian'),
              ),
            )
          ],
        ),
      ),
    );
  }
}

class _PurchaseRow {
  int? itemId;
  int price = 0;
  double qty = 1;
  final TextEditingController qtyC = TextEditingController(text: '1');
  final TextEditingController priceC = TextEditingController(text: '0');

  _PurchaseRow();

  void recalc() {
    qty = double.tryParse(qtyC.text) ?? 0;
    price = int.tryParse(priceC.text) ?? 0;
  }

  int get subtotal => (qty * price).round();
}

String _rp(num n) => 'Rp ${n.toStringAsFixed(0)}';
