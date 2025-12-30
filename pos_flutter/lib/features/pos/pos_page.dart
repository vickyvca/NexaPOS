import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../repositories/product_repository.dart';
import '../../repositories/sales_repository.dart';
import '../../repositories/settings_repository.dart';
import '../../repositories/warehouse_repository.dart';
import '../../services/printing/print_service.dart';
import '../../services/printing/receipt_template.dart';
import '../../state/providers.dart';

class POSPage extends ConsumerStatefulWidget {
  const POSPage({super.key});

  @override
  ConsumerState<POSPage> createState() => _POSPageState();
}

class _POSPageState extends ConsumerState<POSPage> {
  final _scanCtrl = TextEditingController();
  final List<_CartItem> _cart = [];
  String _priceLevel = 'H1';
  bool _ppn = false;
  String? _warehouseId;
  List<Map<String, Object?>> _warehouses = const [];

  @override
  void initState() {
    super.initState();
    _loadWarehouses();
  }

  Future<void> _loadWarehouses() async {
    final repo = ref.read(warehouseRepositoryProvider);
    _warehouses = await repo.list();
    if (_warehouses.isNotEmpty) _warehouseId = (_warehouses.first['id'] as String);
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('POS Kasir'),
        actions: [
          IconButton(
            onPressed: () => context.go('/products'),
            icon: const Icon(Icons.list),
            tooltip: 'Produk',
          ),
          IconButton(
            onPressed: () => context.go('/settings/printer'),
            icon: const Icon(Icons.print),
            tooltip: 'Printer',
          ),
        ],
      ),
      body: Row(
        children: [
          Expanded(
            child: Column(
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
                        onChanged: (v) => setState(() => _warehouseId = v),
                      ),
                      const Spacer(),
                      SegmentedButton<String>(
                        segments: const [
                          ButtonSegment(value: 'H1', label: Text('H1')),
                          ButtonSegment(value: 'H2', label: Text('H2')),
                          ButtonSegment(value: 'GROSIR', label: Text('G')),
                        ],
                        selected: {_priceLevel},
                        onSelectionChanged: (s) => setState(() => _priceLevel = s.first),
                      ),
                      const SizedBox(width: 16),
                      Row(children: [
                        const Text('PPN'),
                        Switch(value: _ppn, onChanged: (v) => setState(() => _ppn = v)),
                      ])
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(8.0),
                  child: TextField(
                    controller: _scanCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Scan barcode / ketik',
                      prefixIcon: Icon(Icons.qr_code_scanner),
                    ),
                    onSubmitted: (val) async {
                      final q = val.trim();
                      if (q.isEmpty) return;
                      final repo = ref.read(productRepositoryProvider);
                      final row = await repo.findByCodeOrName(q);
                      if (row == null) {
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Produk tidak ditemukan: $q')));
                        }
                        return;
                      }
                      final price = _priceForLevel(row, _priceLevel);
                      setState(() {
                        _cart.add(_CartItem(
                          productId: row['id'] as String,
                          name: (row['name'] ?? '') as String,
                          qty: 1,
                          price: price,
                        ));
                        _scanCtrl.clear();
                      });
                    },
                  ),
                ),
                Expanded(
                  child: ListView.separated(
                    itemCount: _cart.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final item = _cart[i];
                      return ListTile(
                        title: Text(item.name),
                        subtitle: Text('Qty: ${item.qty} x ${item.price}'),
                        trailing: Text('${item.qty * item.price}'),
                        onLongPress: () => setState(() => _cart.removeAt(i)),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
          SizedBox(
            width: 360,
            child: Column(
              children: [
                const SizedBox(height: 12),
                _totalsCard(),
                const Spacer(),
                Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
                    onPressed: _cart.isEmpty || _warehouseId == null
                        ? null
                        : () async {
                            await _checkout();
                          },
                    icon: const Icon(Icons.payment),
                    label: const Text('Bayar'),
                  ),
                ),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _totalsCard() {
    final subtotal = _cart.fold<int>(0, (sum, e) => sum + e.qty * e.price);
    final tax = _ppn ? (subtotal * 11 / 100).round() : 0;
    final total = subtotal + tax;
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 12),
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          children: [
            _row('Subtotal', '$subtotal'),
            _row('PPN', '$tax'),
            const Divider(),
            _row('Total', '$total', isBold: true),
          ],
        ),
      ),
    );
  }

  Widget _row(String l, String r, {bool isBold = false}) {
    return Row(
      children: [
        Expanded(child: Text(l, style: TextStyle(fontWeight: isBold ? FontWeight.bold : null))),
        Text(r, style: TextStyle(fontWeight: isBold ? FontWeight.bold : null)),
      ],
    );
  }
}

class _CartItem {
  final String productId;
  final String name;
  final int qty;
  final int price;
  _CartItem({required this.productId, required this.name, required this.qty, required this.price});
}

int _priceForLevel(Map<String, Object?> row, String level) {
  switch (level) {
    case 'H1':
      return (row['price_h1'] as int?) ?? 0;
    case 'H2':
      return (row['price_h2'] as int?) ?? 0;
    case 'GROSIR':
      return (row['price_grosir'] as int?) ?? 0;
    default:
      return 0;
  }
}

String _newId() {
  final rand = Random.secure();
  final bytes = List<int>.generate(12, (_) => rand.nextInt(256));
  return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
}

Future<void> _noop() async {}

extension on _POSPageState {
  Future<void> _checkout() async {
    final salesRepo = ref.read(salesRepositoryProvider);
    final settings = ref.read(settingsRepositoryProvider);
    final id = _newId();
    final items = _cart
        .map((e) => SalesItemInput(productId: e.productId, qty: e.qty, price: e.price))
        .toList(growable: false);
    final subtotal = _cart.fold<int>(0, (s, e) => s + e.qty * e.price);
    final tax = _ppn ? (subtotal * 11 / 100).round() : 0;
    final number = await salesRepo.createSale(
      id: id,
      warehouseId: _warehouseId!,
      items: items,
      taxPpn: tax,
    );

    // Print
    final method = await settings.getValue('printer.backend') ?? (Platform.isWindows ? 'USB' : 'LAN');
    final ip = await settings.getValue('printer.ip');
    final port = int.tryParse((await settings.getValue('printer.port')) ?? '9100');
    final printerName = await settings.getValue('printer.usb.name');
    final paperStr = await settings.getValue('printer.paper') ?? '80mm';
    final paper = paperStr == '80mm' ? PaperSize.mm80 : PaperSize.mm58;
    final svc = PrintService(paperSize: paper);
    await svc.printSaleReceipt(
      method: method,
      ip: ip,
      port: port,
      printerName: printerName,
      data: ReceiptData(
        title: 'STRUK PENJUALAN',
        invoiceNumber: number,
        date: DateTime.now(),
        items: _cart
            .map((e) => ReceiptItem(name: e.name, qty: e.qty, price: e.price, total: e.qty * e.price))
            .toList(growable: false),
        subtotal: subtotal,
        discount: 0,
        tax: tax,
        total: subtotal + tax,
      ),
    );

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Transaksi tersimpan: $number')));
      setState(() => _cart.clear());
    }
  }
}
