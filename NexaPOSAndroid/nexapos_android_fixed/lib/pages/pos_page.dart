import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/cart_item.dart';
import '../models/sale.dart';
import '../models/item.dart';
import '../services/repository.dart';
import 'receipt_page.dart';

final cartProvider = StateNotifierProvider<CartNotifier, List<CartItem>>((ref) => CartNotifier());

class CartNotifier extends StateNotifier<List<CartItem>> {
  CartNotifier() : super([]);

  void add(CartItem item) {
    final idx = state.indexWhere((e) => e.id == item.id && e.batchId == item.batchId);
    if (idx >= 0) {
      final updated = [...state];
      final exist = updated[idx];
      updated[idx] = exist.copyWith(qty: exist.qty + 1);
      state = updated;
    } else {
      state = [...state, item];
    }
  }

  void updateQty(int idx, double delta) {
    final updated = [...state];
    final it = updated[idx];
    final newQty = it.qty + delta;
    if (newQty <= 0) {
      updated.removeAt(idx);
    } else {
      updated[idx] = it.copyWith(qty: newQty);
    }
    state = updated;
  }

  void clear() => state = [];
}

class PosPage extends ConsumerStatefulWidget {
  const PosPage({super.key});

  @override
  ConsumerState<PosPage> createState() => _PosPageState();
}

class _PosPageState extends ConsumerState<PosPage> {
  List<Item> items = [];
  String keyword = '';
  bool loading = true;
  String payMethodDefault = 'cash';
  int taxDefault = 0;
  int? lastSaleId;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() => loading = true);
    items = await Repository().getItems();
    // could fetch settings later
    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final total = cart.fold<int>(0, (p, c) => p + c.subtotal);
    int discountTotal = 0;
    int tax = 0;
    final filtered = keyword.isEmpty
        ? items
        : items.where((e) => e.name.toLowerCase().contains(keyword.toLowerCase()) || (e.code.toLowerCase().contains(keyword.toLowerCase()))).toList();

    return Scaffold(
      appBar: AppBar(title: const Text('POS')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          decoration: const InputDecoration(
                            prefixIcon: Icon(Icons.search),
                            labelText: 'Scan / Cari barang',
                            filled: true,
                          ),
                          onChanged: (v) => setState(() => keyword = v),
                          onSubmitted: (v) => setState(() => keyword = v),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        tooltip: 'Refresh',
                        onPressed: load,
                        icon: const Icon(Icons.refresh),
                      )
                    ],
                  ),
                ),
                Expanded(
                  child: Row(
                    children: [
                      Expanded(
                        flex: 6,
                        child: GridView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 1.2,
                            crossAxisSpacing: 10,
                            mainAxisSpacing: 10,
                          ),
                          itemCount: filtered.length,
                          itemBuilder: (ctx, i) {
                            final it = filtered[i];
                            return _ItemCard(
                              item: it,
                              onAdd: () => ref.read(cartProvider.notifier).add(CartItem(
                                    id: it.id,
                                    name: it.name,
                                    price: it.sellPrice,
                                  )),
                            );
                          },
                        ),
                      ),
                      Expanded(
                        flex: 4,
                      child: Container(
                          margin: const EdgeInsets.only(right: 12, bottom: 12),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0f172a),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFF1f2937)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Cart', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 8),
                              Expanded(
                                child: cart.isEmpty
                                    ? const Center(child: Text('Cart kosong', style: TextStyle(color: Colors.grey)))
                                    : ListView.builder(
                                        itemCount: cart.length,
                                        itemBuilder: (ctx, i) {
                                          final it = cart[i];
                                          return Card(
                                            color: const Color(0xFF111827),
                                            child: ListTile(
                                              title: Text(it.name),
                                              subtitle: Text('Harga: ${_rp(it.price)} | Qty: ${it.qty}'),
                                              trailing: Column(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  IconButton(
                                                    onPressed: () => ref.read(cartProvider.notifier).updateQty(i, 1),
                                                    icon: const Icon(Icons.add_circle_outline),
                                                  ),
                                                  IconButton(
                                                    onPressed: () => ref.read(cartProvider.notifier).updateQty(i, -1),
                                                    icon: const Icon(Icons.remove_circle_outline),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          );
                                        },
                                      ),
                              ),
                              const SizedBox(height: 8),
                              Text('Total: ${_rp(total)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 8),
                              ElevatedButton.icon(
                                icon: const Icon(Icons.wallet),
                                onPressed: cart.isEmpty ? null : () => _checkoutDialog(context, cart, total, discountTotal, tax),
                                label: const Text('Bayar'),
                              ),
                              const SizedBox(height: 8),
                              ElevatedButton.icon(
                                icon: const Icon(Icons.print),
                                onPressed: lastSaleId == null ? null : () {
                                  Navigator.push(context, MaterialPageRoute(builder: (_) => ReceiptPage(saleId: lastSaleId!)));
                                },
                                label: const Text('Cetak / PDF'),
                              )
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  void _checkoutDialog(BuildContext context, List<CartItem> cart, int total, int discountTotal, int tax) {
    final payC = TextEditingController(text: total.toString());
    final discC = TextEditingController(text: '0');
    final taxC = TextEditingController(text: '0');
    String payMethod = 'cash';
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF0f172a),
        title: const Text('Checkout'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Subtotal'), Text(_rp(total))]),
            TextField(controller: discC, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Diskon')),
            TextField(controller: taxC, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Pajak')),
            DropdownButtonFormField<String>(
              initialValue: payMethod,
              decoration: const InputDecoration(labelText: 'Metode'),
              items: const [
                DropdownMenuItem(value: 'cash', child: Text('Cash')),
                DropdownMenuItem(value: 'transfer', child: Text('Transfer')),
                DropdownMenuItem(value: 'qris', child: Text('QRIS')),
              ],
              onChanged: (v) => payMethod = v ?? 'cash',
            ),
            TextField(controller: payC, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Bayar')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              final disc = int.tryParse(discC.text) ?? 0;
              final taxVal = int.tryParse(taxC.text) ?? 0;
              final pay = int.tryParse(payC.text) ?? 0;
              final grand = total - disc + taxVal;
              if (pay < grand) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Bayar kurang')));
                return;
              }
              final repo = Repository();
              final saleNo = 'SL${DateTime.now().millisecondsSinceEpoch}';
              final saleId = await repo.saveSale(
                Sale(
                  saleNo: saleNo,
                  date: DateTime.now().toIso8601String().substring(0, 10),
                  total: total,
                  discount: disc,
                  grandTotal: grand,
                  paymentMethod: payMethod,
                  cashPaid: pay,
                  changeAmount: pay - grand,
                  items: cart,
                ),
              );
              ref.read(cartProvider.notifier).clear();
              setState(() => lastSaleId = saleId);
              if (context.mounted) {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Transaksi tersimpan')));
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }
}

class _ItemCard extends StatelessWidget {
  final Item item;
  final VoidCallback onAdd;
  const _ItemCard({required this.item, required this.onAdd});

  @override
  Widget build(BuildContext context) {
    return Card(
      color: const Color(0xFF111827),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: const BorderSide(color: Color(0xFF1f2937))),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(item.name, style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text(item.code, style: const TextStyle(color: Colors.grey)),
            const Spacer(),
            Text(_rp(item.sellPrice), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF7dd3fc))),
            const SizedBox(height: 4),
            Text('Stok: ${item.stock}', style: const TextStyle(color: Colors.grey)),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                icon: const Icon(Icons.add),
                onPressed: onAdd,
                label: const Text('Tambah'),
              ),
            )
          ],
        ),
      ),
    );
  }
}

String _rp(num n) => 'Rp ${n.toStringAsFixed(0)}';
