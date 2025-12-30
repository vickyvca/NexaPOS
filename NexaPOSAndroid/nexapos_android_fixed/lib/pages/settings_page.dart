import 'package:flutter/material.dart';
import '../services/repository.dart';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  final storeNameC = TextEditingController();
  final storeAddrC = TextEditingController();
  final taxC = TextEditingController(text: '0');

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final repo = Repository();
    storeNameC.text = await repo.getSetting('store_name') ?? 'NexaPOS';
    storeAddrC.text = await repo.getSetting('store_addr') ?? '';
    taxC.text = await repo.getSetting('tax_default') ?? '0';
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pengaturan')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: storeNameC, decoration: const InputDecoration(labelText: 'Nama Toko')),
            TextField(controller: storeAddrC, decoration: const InputDecoration(labelText: 'Alamat')),
            TextField(controller: taxC, decoration: const InputDecoration(labelText: 'Pajak Default'), keyboardType: TextInputType.number),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () async {
                final repo = Repository();
                await repo.setSetting('store_name', storeNameC.text);
                await repo.setSetting('store_addr', storeAddrC.text);
                await repo.setSetting('tax_default', taxC.text);
                if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tersimpan')));
              },
              child: const Text('Simpan'),
            )
          ],
        ),
      ),
    );
  }
}
