import 'package:esc_pos_utils/esc_pos_utils.dart';

class ReceiptItem {
  final String name;
  final int qty;
  final int price;
  final int total;
  ReceiptItem({required this.name, required this.qty, required this.price, required this.total});
}

class ReceiptData {
  final String title;
  final String invoiceNumber;
  final DateTime date;
  final List<ReceiptItem> items;
  final int subtotal;
  final int discount;
  final int tax;
  final int total;
  ReceiptData({
    required this.title,
    required this.invoiceNumber,
    required this.date,
    required this.items,
    required this.subtotal,
    required this.discount,
    required this.tax,
    required this.total,
  });
}

class ReceiptTemplate {
  static List<int> build({required PaperSize paperSize, required CapabilityProfile profile, required ReceiptData data}) {
    final gen = Generator(paperSize, profile);
    final bytes = <int>[];
    bytes.addAll(gen.text(data.title, styles: const PosStyles(align: PosAlign.center, bold: true)));
    bytes.addAll(gen.text('No: ${data.invoiceNumber}', styles: const PosStyles(align: PosAlign.center)));
    bytes.addAll(gen.text('${data.date}', styles: const PosStyles(align: PosAlign.center)));
    bytes.addAll(gen.hr());
    for (final it in data.items) {
      bytes.addAll(gen.row([
        PosColumn(text: it.name, width: 6),
        PosColumn(text: '${it.qty} x ${it.price}', width: 3, styles: const PosStyles(align: PosAlign.right)),
        PosColumn(text: '${it.total}', width: 3, styles: const PosStyles(align: PosAlign.right)),
      ]));
    }
    bytes.addAll(gen.hr());
    bytes.addAll(gen.row([
      PosColumn(text: 'Subtotal', width: 6),
      PosColumn(text: '', width: 3),
      PosColumn(text: '${data.subtotal}', width: 3, styles: const PosStyles(align: PosAlign.right)),
    ]));
    if (data.discount != 0) {
      bytes.addAll(gen.row([
        PosColumn(text: 'Diskon', width: 6),
        PosColumn(text: '', width: 3),
        PosColumn(text: '-${data.discount}', width: 3, styles: const PosStyles(align: PosAlign.right)),
      ]));
    }
    if (data.tax != 0) {
      bytes.addAll(gen.row([
        PosColumn(text: 'PPN', width: 6),
        PosColumn(text: '', width: 3),
        PosColumn(text: '${data.tax}', width: 3, styles: const PosStyles(align: PosAlign.right)),
      ]));
    }
    bytes.addAll(gen.hr());
    bytes.addAll(gen.text('TOTAL: ${data.total}', styles: const PosStyles(align: PosAlign.right, bold: true)));
    bytes.addAll(gen.hr());
    bytes.addAll(gen.text('Terima kasih', styles: const PosStyles(align: PosAlign.center)));
    bytes.addAll(gen.cut());
    return bytes;
  }
}
