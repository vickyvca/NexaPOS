import 'dart:ffi';

import 'package:ffi/ffi.dart';
import 'package:win32/win32.dart';

class WindowsPrinters {
  static Future<List<String>> listPrinters() async {
    final flags = PRINTER_ENUM_LOCAL | PRINTER_ENUM_CONNECTIONS;
    final pcbNeeded = calloc<Uint32>();
    final pcReturned = calloc<Uint32>();
    // First call to get required buffer size
    EnumPrinters(flags, nullptr, 4, nullptr, 0, pcbNeeded, pcReturned);
    if (pcbNeeded.value == 0) {
      calloc.free(pcbNeeded);
      calloc.free(pcReturned);
      return [];
    }
    final buf = calloc<Uint8>(pcbNeeded.value);
    try {
      final ok = EnumPrinters(flags, nullptr, 4, buf, pcbNeeded.value, pcbNeeded, pcReturned);
      if (ok == 0) {
        return [];
      }
      final count = pcReturned.value;
      final result = <String>[];
      final sizeOfStruct = sizeOf<PRINTER_INFO_4>();
      for (var i = 0; i < count; i++) {
        final p = Pointer<PRINTER_INFO_4>.fromAddress(buf.address + i * sizeOfStruct);
        final name = p.ref.pPrinterName.toDartString();
        if (name.isNotEmpty) result.add(name);
      }
      return result;
    } finally {
      calloc.free(buf);
      calloc.free(pcbNeeded);
      calloc.free(pcReturned);
    }
  }
}
