import 'package:flutter/material.dart';
import '../services/repository.dart';
import '../models/item.dart';
import '../models/supplier.dart';
import '../models/category.dart';

class MasterPage extends StatefulWidget {
  const MasterPage({super.key});

  @override
  State<MasterPage> createState() => _MasterPageState();
}

class _MasterPageState extends State<MasterPage> with SingleTickerProviderStateMixin {
  late TabController tab;
  final repo = Repository();
  List<Item> items = [];
  List<Supplier> suppliers = [];
  List<Category> categories = [];
  bool loading = true;

  @override
  void initState() {
    super.initState();
    tab = TabController(length: 2, vsync: this);
    load();
  }

  Future<void> load() async {
    loading = true;
    setState(() {});
    items = await repo.getItems();
    suppliers = await repo.getSuppliers();
    categories = await repo.getCategories();
    loading = false;
    setState(() {});
  }

  Future<void> addItemDialog() async {
    final nameC = TextEditingController();
    final codeC = TextEditingController();
    final priceC = TextEditingController();
    final buyC = TextEditingController();
    final stockC = TextEditingController(text: '0');
    Category? selectedCat = categories.isNotEmpty ? categories.first : null;
    await showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Tambah Item'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: nameC, decoration: const InputDecoration(labelText: 'Nama')),
            TextField(controller: codeC, decoration: const InputDecoration(labelText: 'Kode')),
            DropdownButtonFormField<Category>(
              initialValue: selectedCat,
              items: categories.map((c) => DropdownMenuItem(value: c, child: Text(c.name))).toList(),
              onChanged: (c) => selectedCat = c,
              decoration: const InputDecoration(labelText: 'Kategori'),
            ),
            TextField(controller: priceC, decoration: const InputDecoration(labelText: 'Harga Jual')),
            TextField(controller: buyC, decoration: const InputDecoration(labelText: 'Harga Beli')),
            TextField(controller: stockC, decoration: const InputDecoration(labelText: 'Stok'), keyboardType: TextInputType.number),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              final price = int.tryParse(priceC.text) ?? 0;
              final buy = int.tryParse(buyC.text) ?? price;
              final stock = double.tryParse(stockC.text) ?? 0;
              await repo.upsertItem(Item(code: codeC.text, name: nameC.text, category: selectedCat?.name, sellPrice: price, buyPrice: buy, stock: stock));
              if (mounted) Navigator.pop(context);
              await load();
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  Future<void> addSupplierDialog() async {
    final nameC = TextEditingController();
    final phoneC = TextEditingController();
    final addrC = TextEditingController();
    await showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Tambah Supplier'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: nameC, decoration: const InputDecoration(labelText: 'Nama')),
            TextField(controller: phoneC, decoration: const InputDecoration(labelText: 'Telepon')),
            TextField(controller: addrC, decoration: const InputDecoration(labelText: 'Alamat')),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              await repo.upsertSupplier(Supplier(name: nameC.text, phone: phoneC.text, address: addrC.text));
              if (mounted) Navigator.pop(context);
              await load();
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Master Data'),
        bottom: TabBar(
          controller: tab,
          tabs: const [
            Tab(text: 'Barang'),
            Tab(text: 'Supplier'),
            Tab(text: 'Kategori'),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          if (tab.index == 0) {
            addItemDialog();
          } else if (tab.index == 1) addSupplierDialog();
          else addCategoryDialog();
        },
        child: const Icon(Icons.add),
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: tab,
              children: [
                _ItemsTab(items: items),
                _SuppliersTab(suppliers: suppliers),
                _CategoriesTab(categories: categories),
              ],
            ),
    );
  }
}

class _ItemsTab extends StatelessWidget {
  final List<Item> items;
  const _ItemsTab({required this.items});

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      itemCount: items.length,
      itemBuilder: (ctx, i) {
        final it = items[i];
        return ListTile(
          title: Text(it.name),
          subtitle: Text('${it.code} • Beli: ${_rp(it.buyPrice)} • Jual: ${_rp(it.sellPrice)} • Stok: ${it.stock}'),
        );
      },
    );
  }
}

class _SuppliersTab extends StatelessWidget {
  final List<Supplier> suppliers;
  const _SuppliersTab({required this.suppliers});

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      itemCount: suppliers.length,
      itemBuilder: (ctx, i) {
        final s = suppliers[i];
        return ListTile(
          title: Text(s.name),
          subtitle: Text('${s.phone ?? '-'} • ${s.address ?? '-'}'),
        );
      },
    );
  }
}

class _CategoriesTab extends StatelessWidget {
  final List<Category> categories;
  const _CategoriesTab({required this.categories});

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      itemCount: categories.length,
      itemBuilder: (ctx, i) {
        final c = categories[i];
        return ListTile(
          title: Text(c.name),
        );
      },
    );
  }
}

extension on _MasterPageState {
  Future<void> addCategoryDialog() async {
    final nameC = TextEditingController();
    await showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Tambah Kategori'),
        content: TextField(controller: nameC, decoration: const InputDecoration(labelText: 'Nama')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              await repo.upsertCategory(Category(name: nameC.text));
              if (mounted) Navigator.pop(context);
              await load();
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }
}

String _rp(num n) => 'Rp ${n.toStringAsFixed(0)}';
