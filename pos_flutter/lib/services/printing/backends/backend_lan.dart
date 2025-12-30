import 'package:esc_pos_printer/esc_pos_printer.dart';
import 'package:esc_pos_utils/esc_pos_utils.dart';

class LanPrinterBackend {
  Future<void> print({required String ip, required int port, required List<int> bytes}) async {
    final profile = await CapabilityProfile.load();
    final printer = NetworkPrinter(PaperSize.mm80, profile);
    final res = await printer.connect(ip, port: port);
    if (res == PosPrintResult.success) {
      printer.rawBytes(bytes);
      printer.disconnect();
    } else {
      throw Exception('Gagal konek ke printer LAN: $res');
    }
  }
}
