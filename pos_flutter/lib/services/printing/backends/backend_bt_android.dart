// Android Bluetooth backend (blue_thermal_printer)
// This is a minimal example; production code should handle device discovery and pairing UI.
import 'dart:io';

import 'package:blue_thermal_printer/blue_thermal_printer.dart';

class AndroidBluetoothPrinterBackend {
  final BlueThermalPrinter _printer = BlueThermalPrinter.instance;

  Future<void> print({required List<int> bytes}) async {
    if (!Platform.isAndroid) {
      throw Exception('Bluetooth hanya di Android');
    }
    final isOn = await _printer.isOn;
    if (isOn != true) {
      throw Exception('Bluetooth tidak aktif');
    }

    // Try connect to the first bonded device (placeholder). In UI we should let user pick.
    final devices = await _printer.getBondedDevices();
    if (devices.isEmpty) {
      throw Exception('Tidak ada device Bluetooth terpasang');
    }
    final device = devices.first;
    final connected = await _printer.isConnected;
    if (connected != true) {
      await _printer.connect(device);
    }
    await _printer.writeBytes(bytes);
    await _printer.disconnect();
  }
}
