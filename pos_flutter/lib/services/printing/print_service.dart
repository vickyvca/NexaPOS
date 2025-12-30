import 'dart:io';

import 'package:esc_pos_utils/esc_pos_utils.dart';

import 'backends/backend_bt_android.dart';
import 'backends/backend_lan.dart';
import 'backends/backend_usb_windows.dart';
import 'receipt_template.dart';

class PrintService {
  final PaperSize paperSize;
  PrintService({required this.paperSize});

  Future<void> testPrint({required String method, String? ip, int? port, String? printerName}) async {
    final profile = await CapabilityProfile.load();
    final generator = Generator(paperSize, profile);
    final List<int> bytes = [];
    bytes.addAll(generator.text('TES PRINT', styles: const PosStyles(align: PosAlign.center, bold: true)));
    bytes.addAll(generator.text('POS Flutter'));
    bytes.addAll(generator.text('------------------------------'));
    bytes.addAll(generator.qrcode('https://example.com', size: QRSize.Size4));
    bytes.addAll(generator.cut());

    await _send(method: method, bytes: bytes, ip: ip, port: port, printerName: printerName);
  }

  Future<void> printSaleReceipt({
    required String method,
    String? ip,
    int? port,
    String? printerName,
    required ReceiptData data,
  }) async {
    final profile = await CapabilityProfile.load();
    final bytes = ReceiptTemplate.build(paperSize: paperSize, profile: profile, data: data);
    await _send(method: method, bytes: bytes, ip: ip, port: port, printerName: printerName);
  }

  Future<void> _send({required String method, required List<int> bytes, String? ip, int? port, String? printerName}) async {
    switch (method) {
      case 'LAN':
        final lan = LanPrinterBackend();
        await lan.print(ip: ip ?? '192.168.1.100', port: port ?? 9100, bytes: bytes);
        break;
      case 'BT':
        if (!Platform.isAndroid) throw Exception('Bluetooth hanya di Android');
        final bt = AndroidBluetoothPrinterBackend();
        await bt.print(bytes: bytes);
        break;
      case 'USB':
        if (!Platform.isWindows) throw Exception('USB hanya di Windows');
        final usb = WindowsUsbPrinterBackend();
        await usb.print(bytes: bytes, printerName: printerName);
        break;
      default:
        throw Exception('Metode tidak dikenal');
    }
  }
}
