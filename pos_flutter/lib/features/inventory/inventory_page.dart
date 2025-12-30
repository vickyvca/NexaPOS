import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../repositories/stock_repository.dart';
import '../../state/providers.dart';

class InventoryPage extends ConsumerStatefulWidget {
  const InventoryPage({super.key});

  @override
  ConsumerState<InventoryPage> createState() => _InventoryPageState();
}

class _InventoryPageState extends ConsumerState<InventoryPage> {
  String? _warehouseId;
  List<Map<String, Object?>> _warehouses = const [];
  List<Map<String, Object?>> _rows = const [];
  final _search = TextEditingController();
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final wrepo = ref.read(warehouseRepositoryProvider);
    _warehouses = await wrepo.list();
    _warehouseId ??= _warehouses.isNotEmpty ? _warehouses.first['id'] as String : null;
    if (_warehouseId != null) {
      final srepo = StockRepository();
      _rows = await srepo.listStocks(_warehouseId!, query: _search.text);
    }
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inventory')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Row(
              children: [
                const Text('Gudang: '),
                const SizedBox(width: 8),
                DropdownButton<String>(
                  value: _warehouseId,
                  items: _warehouses
                      .map((w) => DropdownMenuItem<String>(
                            value: w['id'] as String,
                            child: Text(w['name'] as String),
                          ))
                      .toList(),
                  onChanged: (v) {
                    setState(() => _warehouseId = v);
                    _load();
                  },
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextField(
                    controller: _search,
                    decoration: const InputDecoration(prefixIcon: Icon(Icons.search), hintText: 'Cari produk...'),
                    onSubmitted: (_) => _load(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(onPressed: _load, child: const Text('Cari')),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : ListView.separated(
                    itemCount: _rows.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final r = _rows[i];
                      return ListTile(
                        title: Text(r['name'] as String),
                        subtitle: Text('Barcode: ${r['barcode'] ?? '-'} | SKU: ${r['sku'] ?? '-'}'),
                        trailing: Text('Qty: ${r['qty']}'),
                        onTap: () async {
                          final target = await showDialog<int>(
                            context: context,
                            builder: (_) => _AdjustDialog(current: r['qty'] as int),
                          );
                          if (target != null && _warehouseId != null) {
                            await StockRepository().setStock(productId: r['id'] as String, warehouseId: _warehouseId!, qty: target, reason: 'ADJUST');
                            _load();
                          }
                        },
                      );
                    },
                  ),
          )
        ],
      ),
    );
  }
}

class _AdjustDialog extends StatefulWidget {
  final int current;
  const _AdjustDialog({required this.current});
  @override
  State<_AdjustDialog> createState() => _AdjustDialogState();
}

class _AdjustDialogState extends State<_AdjustDialog> {
  late final TextEditingController _ctrl;
  @override
  void initState() { super.initState(); _ctrl = TextEditingController(text: widget.current.toString()); }
  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Set Qty'),
      content: SizedBox(width: 280, child: TextField(controller: _ctrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Qty baru'))),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        FilledButton(onPressed: () => Navigator.pop<int>(context, int.tryParse(_ctrl.text.trim()) ?? widget.current), child: const Text('Simpan')),
      ],
    );
  }
}
