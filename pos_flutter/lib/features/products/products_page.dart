import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../state/providers.dart';

class ProductsPage extends ConsumerStatefulWidget {
  const ProductsPage({super.key});

  @override
  ConsumerState<ProductsPage> createState() => _ProductsPageState();
}

class _ProductsPageState extends ConsumerState<ProductsPage> {
  final _search = TextEditingController();
  List<Map<String, Object?>> _rows = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final repo = ref.read(productRepositoryProvider);
    _rows = await repo.list(query: _search.text);
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Produk'),
        actions: [
          IconButton(
            onPressed: () => context.go('/pos'),
            icon: const Icon(Icons.point_of_sale),
            tooltip: 'Ke POS',
          )
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _search,
                    decoration: const InputDecoration(
                      hintText: 'Cari nama/barcode/SKU',
                      prefixIcon: Icon(Icons.search),
                    ),
                    onSubmitted: (_) => _load(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton.icon(
                  onPressed: _load,
                  icon: const Icon(Icons.search),
                  label: const Text('Cari'),
                ),
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
                        title: Text((r['name'] ?? '') as String),
                        subtitle: Text('Barcode: ${r['barcode'] ?? '-'} | SKU: ${r['sku'] ?? '-'}'),
                        trailing: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text('H1: ${r['price_h1'] ?? '-'}'),
                            Text('H2: ${r['price_h2'] ?? '-'} | G: ${r['price_grosir'] ?? '-'}'),
                          ],
                        ),
                        onTap: () async {
                          await showDialog(
                            context: context,
                            builder: (_) => _ProductFormDialog(row: r),
                          );
                          _load();
                        },
                      );
                    },
                  ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () async {
          await showDialog(
            context: context,
            builder: (_) => const _ProductFormDialog(row: null),
          );
          _load();
        },
        child: const Icon(Icons.add),
      ),
    );
  }
}

class _ProductFormDialog extends ConsumerStatefulWidget {
  final Map<String, Object?>? row;
  const _ProductFormDialog({required this.row});

  @override
  ConsumerState<_ProductFormDialog> createState() => _ProductFormDialogState();
}

class _ProductFormDialogState extends ConsumerState<_ProductFormDialog> {
  final _name = TextEditingController();
  final _barcode = TextEditingController();
  final _sku = TextEditingController();
  final _unit = TextEditingController();
  final _h1 = TextEditingController();
  final _h2 = TextEditingController();
  final _g = TextEditingController();

  @override
  void initState() {
    super.initState();
    final r = widget.row;
    _name.text = (r?['name'] ?? '') as String;
    _barcode.text = (r?['barcode'] ?? '') as String;
    _sku.text = (r?['sku'] ?? '') as String;
    _unit.text = (r?['unit'] ?? '') as String;
    _h1.text = (r?['price_h1']?.toString() ?? '');
    _h2.text = (r?['price_h2']?.toString() ?? '');
    _g.text = (r?['price_grosir']?.toString() ?? '');
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.row == null ? 'Tambah Produk' : 'Edit Produk'),
      content: SizedBox(
        width: 420,
        child: SingleChildScrollView(
          child: Column(
            children: [
              TextField(decoration: const InputDecoration(labelText: 'Nama'), controller: _name),
              TextField(decoration: const InputDecoration(labelText: 'Barcode'), controller: _barcode),
              TextField(decoration: const InputDecoration(labelText: 'SKU'), controller: _sku),
              TextField(decoration: const InputDecoration(labelText: 'Satuan'), controller: _unit),
              const SizedBox(height: 8),
              Row(children: [
                Flexible(child: TextField(decoration: const InputDecoration(labelText: 'H1'), controller: _h1, keyboardType: TextInputType.number)),
                const SizedBox(width: 8),
                Flexible(child: TextField(decoration: const InputDecoration(labelText: 'H2'), controller: _h2, keyboardType: TextInputType.number)),
                const SizedBox(width: 8),
                Flexible(child: TextField(decoration: const InputDecoration(labelText: 'Grosir'), controller: _g, keyboardType: TextInputType.number)),
              ]),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        FilledButton(
          onPressed: () async {
            final repo = ref.read(productRepositoryProvider);
            final id = (widget.row?['id'] as String?) ?? _newId();
            await repo.upsert(
              Product(
                id: id,
                name: _name.text.trim(),
                barcode: _barcode.text.trim().isEmpty ? null : _barcode.text.trim(),
                unit: _unit.text.trim().isEmpty ? null : _unit.text.trim(),
                sku: _sku.text.trim().isEmpty ? null : _sku.text.trim(),
              ),
              priceH1: int.tryParse(_h1.text.trim()),
              priceH2: int.tryParse(_h2.text.trim()),
              priceGrosir: int.tryParse(_g.text.trim()),
            );
            if (context.mounted) Navigator.pop(context);
          },
          child: const Text('Simpan'),
        ),
      ],
    );
  }

  String _newId() {
    final rand = Random.secure();
    final bytes = List<int>.generate(12, (_) => rand.nextInt(256));
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }
}
