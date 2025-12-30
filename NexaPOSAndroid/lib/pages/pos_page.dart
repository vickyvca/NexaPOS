import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/cart_item.dart';
import '../services/repository.dart';

final cartProvider = StateNotifierProvider<CartNotifier, List<CartItem>>((ref) => CartNotifier());

class CartNotifier extends StateNotifier<List<CartItem>> {
  CartNotifier() : super([]);

  void add(CartItem item) {
    final idx = state.indexWhere((e) => e.id == item.id && e.batchId == item.batchId);
    if (idx >= 0) {
      final updated = [...state];
      final exist = updated[idx];
      updated[idx] = CartItem(
        id: exist.id,
        name: exist.name,
        price: exist.price,
        qty: exist.qty + 1,
        discount: exist.discount,
        level: exist.level,
        batchId: exist.batchId,
        batchNo: exist.batchNo,
        expiry: exist.expiry,
      );
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
      updated[idx] = CartItem(
        id: it.id,
        name: it.name,
        price: it.price,
        qty: newQty,
        discount: it.discount,
        level: it.level,
        batchId: it.batchId,
        batchNo: it.batchNo,
        expiry: it.expiry,
      );
    }
    state = updated;
  }

  void clear() => state = [];
}

class PosPage extends ConsumerWidget {
  const PosPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cart = ref.watch(cartProvider);
    final total = cart.fold<int>(0, (p, c) => p + c.subtotal);
    return Scaffold(
      appBar: AppBar(title: const Text('POS Offline')),
      body: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            _SearchBox(onSelected: (it) {
              ref.read(cartProvider.notifier).add(it);
            }),
            const SizedBox(height: 8),
            Expanded(
              child: ListView.builder(
                itemCount: cart.length,
                itemBuilder: (ctx, i) {
                  final it = cart[i];
                  return Card(
                    child: ListTile(
                      title: Text(it.name),
                      subtitle: Text('Harga: ${_rp(it.price)} | Qty: ${it.qty} | Subtotal: ${_rp(it.subtotal)}'),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(onPressed: () => ref.read(cartProvider.notifier).updateQty(i, -1), icon: const Icon(Icons.remove_circle_outline)),
                          IconButton(onPressed: () => ref.read(cartProvider.notifier).updateQty(i, 1), icon: const Icon(Icons.add_circle_outline)),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Total: ${_rp(total)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                ElevatedButton(
                  onPressed: cart.isEmpty ? null : () async {
                    final repo = Repository();
                    final saleNo = 'SL${DateTime.now().millisecondsSinceEpoch}';
                    await repo.saveSale(
                      Sale(
                        saleNo: saleNo,
                        date: DateTime.now().toIso8601String().substring(0,10),
                        total: total,
                        discount: 0,
                        grandTotal: total,
                        paymentMethod: 'cash',
                        cashPaid: total,
                        changeAmount: 0,
                        items: cart,
                      ),
                    );
                    ref.read(cartProvider.notifier).clear();
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Transaksi tersimpan (offline)')));
                    }
                  },
                  child: const Text('Bayar & Simpan'),
                )
              ],
            )
          ],
        ),
      ),
    );
  }
}

class _SearchBox extends StatefulWidget {
  final void Function(CartItem) onSelected;
  const _SearchBox({required this.onSelected});

  @override
  State<_SearchBox> createState() => _SearchBoxState();
}

class _SearchBoxState extends State<_SearchBox> {
  final TextEditingController c = TextEditingController();
  List<Map<String, Object?>> results = [];
  bool loading = false;

  Future<void> search(String q) async {
    setState(() => loading = true);
    final repo = Repository();
    final items = await repo.getItems(keyword: q);
    results = items.take(8).map((e) => {
      'id': e.id,
      'name': e.name,
      'code': e.code,
      'price': e.sellPrice,
      'batch_id': null,
      'batch_no': null,
      'expiry': null,
    }).toList();
    setState(() => loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TextField(
          controller: c,
          decoration: InputDecoration(
            labelText: 'Scan / Cari barang',
            suffixIcon: loading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : IconButton(
              icon: const Icon(Icons.search),
              onPressed: () => search(c.text),
            ),
          ),
          onSubmitted: search,
        ),
        if (results.isNotEmpty)
          Card(
            child: Column(
              children: results.map((r) => ListTile(
                title: Text(r['name'] as String),
                subtitle: Text(r['code'] as String),
                onTap: () {
                  widget.onSelected(CartItem(
                    id: r['id'] as int?,
                    name: r['name'] as String,
                    price: (r['price'] as int?) ?? 0,
                  ));
                  setState(() => results = []);
                  c.clear();
                },
              )).toList(),
            ),
          )
      ],
    );
  }
}

String _rp(num n) => 'Rp ${n.toStringAsFixed(0)}';
