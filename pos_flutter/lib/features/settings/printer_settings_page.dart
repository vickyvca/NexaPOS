import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../repositories/settings_repository.dart';
import '../../services/printing/print_service.dart';
import '../../services/printing/windows_printers.dart';
import '../../state/providers.dart';

class PrinterSettingsPage extends ConsumerStatefulWidget {
  const PrinterSettingsPage({super.key});

  @override
  ConsumerState<PrinterSettingsPage> createState() => _PrinterSettingsPageState();
}

class _PrinterSettingsPageState extends ConsumerState<PrinterSettingsPage> {
  String _paper = '80mm';
  String _backend = 'LAN'; // LAN / BT (Android) / USB (Windows)
  final _ip = TextEditingController(text: '192.168.1.100');
  final _port = TextEditingController(text: '9100');
  final _printerName = TextEditingController();
  List<String> _printers = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final settings = ref.read(settingsRepositoryProvider);
    _paper = (await settings.getValue('printer.paper')) ?? '80mm';
    _backend = (await settings.getValue('printer.backend')) ?? (Platform.isWindows ? 'USB' : 'LAN');
    _ip.text = (await settings.getValue('printer.ip')) ?? '192.168.1.100';
    _port.text = (await settings.getValue('printer.port')) ?? '9100';
    _printerName.text = (await settings.getValue('printer.usb.name')) ?? '';
    if (Platform.isWindows) {
      _printers = await WindowsPrinters.listPrinters();
    }
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final isAndroid = Platform.isAndroid;
    final isWindows = Platform.isWindows;

    return Scaffold(
      appBar: AppBar(title: const Text('Pengaturan Printer')),
      body: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Ukuran Kertas'),
            const SizedBox(height: 6),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: '58mm', label: Text('58mm')),
                ButtonSegment(value: '80mm', label: Text('80mm')),
              ],
              selected: {_paper},
              onSelectionChanged: (s) => setState(() => _paper = s.first),
            ),
            const SizedBox(height: 12),
            const Text('Metode Koneksi'),
            DropdownButton<String>(
              value: _backend,
              items: [
                const DropdownMenuItem(value: 'LAN', child: Text('LAN / TCP')),
                if (isAndroid) const DropdownMenuItem(value: 'BT', child: Text('Bluetooth (Android)')),
                if (isWindows) const DropdownMenuItem(value: 'USB', child: Text('USB (Windows)')),
              ],
              onChanged: (v) => setState(() => _backend = v ?? _backend),
            ),
            if (_backend == 'LAN') ...[
              TextField(controller: _ip, decoration: const InputDecoration(labelText: 'IP Printer')),
              TextField(controller: _port, decoration: const InputDecoration(labelText: 'Port (9100)'), keyboardType: TextInputType.number),
            ] else if (_backend == 'USB' && isWindows) ...[
              DropdownButtonFormField<String>(
                initialValue: _printers.contains(_printerName.text) ? _printerName.text : null,
                items: _printers.map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
                onChanged: (v) => setState(() => _printerName.text = v ?? ''),
                decoration: const InputDecoration(labelText: 'Printer (Windows)'),
              ),
              TextField(controller: _printerName, decoration: const InputDecoration(labelText: 'Nama Printer (opsional)')),
            ],
            const Spacer(),
            Row(
              children: [
                OutlinedButton(
                  onPressed: () async {
                    final paper = _paper == '80mm' ? PaperSize.mm80 : PaperSize.mm58;
                    final svc = PrintService(paperSize: paper);
                    await svc.testPrint(
                      method: _backend,
                      ip: _ip.text.trim(),
                      port: int.tryParse(_port.text.trim()) ?? 9100,
                      printerName: _printerName.text.trim().isEmpty ? null : _printerName.text.trim(),
                    );
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tes print dikirim')));
                    }
                  },
                  child: const Text('Tes Print'),
                ),
                const SizedBox(width: 12),
                FilledButton(
                  onPressed: () async {
                    final settings = ref.read(settingsRepositoryProvider);
                    await settings.setValue('printer.paper', _paper);
                    await settings.setValue('printer.backend', _backend);
                    await settings.setValue('printer.ip', _ip.text.trim());
                    await settings.setValue('printer.port', _port.text.trim());
                    await settings.setValue('printer.usb.name', _printerName.text.trim());
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pengaturan disimpan')));
                    }
                  },
                  child: const Text('Simpan'),
                )
              ],
            )
          ],
        ),
      ),
    );
  }
}
