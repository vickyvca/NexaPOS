// Windows USB backend via Win32 RAW printing to installed printer queue.
// Requires the printer to be installed in Windows and accept RAW ESC/POS.
import 'dart:ffi';
import 'dart:typed_data';

import 'package:ffi/ffi.dart';
import 'package:win32/win32.dart';

class WindowsUsbPrinterBackend {
  Future<void> print({required List<int> bytes, String? printerName}) async {
    final name = (printerName ?? await _getDefaultPrinterName()).toNativeUtf16();
    final hPrinterPtr = calloc<IntPtr>();
    try {
      final opened = OpenPrinter(name, hPrinterPtr, nullptr);
      if (opened == 0) {
        throw Exception('Gagal membuka printer');
      }
      final hPrinter = hPrinterPtr.value;
      final docInfo = calloc<DOC_INFO_1>();
      docInfo.ref.pDocName = 'POS Receipt'.toNativeUtf16();
      docInfo.ref.pOutputFile = nullptr;
      docInfo.ref.pDatatype = 'RAW'.toNativeUtf16();
      final started = StartDocPrinter(hPrinter, 1, docInfo.cast());
      if (started == 0) {
        ClosePrinter(hPrinter);
        throw Exception('Gagal StartDocPrinter');
      }
      StartPagePrinter(hPrinter);
      final dataPtr = calloc<Uint8>(bytes.length);
      final writtenPtr = calloc<Uint32>();
      try {
        final u8 = Uint8List.fromList(bytes);
        dataPtr.asTypedList(bytes.length).setAll(0, u8);
        final ok = WritePrinter(hPrinter, dataPtr.cast(), bytes.length, writtenPtr);
        if (ok == 0 || writtenPtr.value != bytes.length) {
          throw Exception('Gagal mengirim data ke printer');
        }
      } finally {
        calloc.free(dataPtr);
        calloc.free(writtenPtr);
      }
      EndPagePrinter(hPrinter);
      EndDocPrinter(hPrinter);
      ClosePrinter(hPrinter);
    } finally {
      calloc.free(name);
      calloc.free(hPrinterPtr);
    }
  }

  Future<String> _getDefaultPrinterName() async {
    final needed = calloc<Uint32>();
    // First call to get size
    GetDefaultPrinter(nullptr, needed);
    final buf = calloc<Uint16>(needed.value);
    try {
      if (GetDefaultPrinter(buf, needed) == 0) {
        throw Exception('Tidak dapat membaca default printer');
      }
      return buf.toDartString();
    } finally {
      calloc.free(buf);
      calloc.free(needed);
    }
  }
}
