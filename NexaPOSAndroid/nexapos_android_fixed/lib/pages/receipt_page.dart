import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import '../services/repository.dart';

class ReceiptPage extends StatefulWidget {
  final int saleId;
  const ReceiptPage({super.key, required this.saleId});

  @override
  State<ReceiptPage> createState() => _ReceiptPageState();
}

class _ReceiptPageState extends State<ReceiptPage> {
  Map<String, Object?>? sale;
  List<Map<String, Object?>> items = [];
  bool loading = true;
  String? savedPath;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final repo = Repository();
    sale = await repo.getSale(widget.saleId);
    items = await repo.getSaleItems(widget.saleId);
    setState(() => loading = false);
  }

  Future<void> exportPdf() async {
    final doc = pw.Document();
    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll57,
        build: (_) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              pw.Text('NexaPOS', style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold)),
              pw.Text('Nota: ${sale?['sale_no']}'),
              pw.Text('Tanggal: ${sale?['date']}'),
              pw.SizedBox(height: 8),
              ...items.map((i) => pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Text('${i['name']}'),
                  pw.Text('${i['qty']} x ${i['price']} = ${i['subtotal']}'),
                  pw.SizedBox(height: 4),
                ],
              )),
              pw.Divider(),
              pw.Text('Total: ${sale?['total']}'),
              pw.Text('Diskon: ${sale?['discount']}'),
              pw.Text('Grand: ${sale?['grand_total']}'),
              pw.Text('Bayar: ${sale?['cash_paid']}'),
              pw.Text('Kembali: ${sale?['change_amount']}'),
            ],
          );
        },
      ),
    );
    final dir = await getApplicationDocumentsDirectory();
    final file = File('${dir.path}/nota_${sale?['sale_no']}.pdf');
    await file.writeAsBytes(await doc.save());
    setState(() => savedPath = file.path);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Disimpan: ${file.path}')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Struk')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Nota: ${sale?['sale_no']}'),
                  Text('Tanggal: ${sale?['date']}'),
                  const SizedBox(height: 8),
                  Expanded(
                    child: ListView.builder(
                      itemCount: items.length,
                      itemBuilder: (ctx, i) {
                        final it = items[i];
                        return ListTile(
                          title: Text('${it['name']}'),
                          subtitle: Text('${it['qty']} x ${it['price']}'),
                          trailing: Text('${it['subtotal']}'),
                        );
                      },
                    ),
                  ),
                  Text('Grand Total: ${sale?['grand_total']}'),
                  Text('Bayar: ${sale?['cash_paid']}'),
                  Text('Kembali: ${sale?['change_amount']}'),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      ElevatedButton.icon(
                        onPressed: exportPdf,
                        icon: const Icon(Icons.picture_as_pdf),
                        label: const Text('Export PDF'),
                      ),
                      const SizedBox(width: 8),
                      if (savedPath != null) Expanded(child: Text('File: $savedPath', style: const TextStyle(fontSize: 12))),
                    ],
                  )
                ],
              ),
            ),
    );
  }
}
