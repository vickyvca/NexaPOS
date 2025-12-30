import 'cart_item.dart';

class Sale {
  final int? id;
  final String saleNo;
  final String date;
  final int total;
  final int discount;
  final int grandTotal;
  final String paymentMethod;
  final int cashPaid;
  final int changeAmount;
  final String? customerName;
  final List<CartItem> items;

  Sale({
    this.id,
    required this.saleNo,
    required this.date,
    required this.total,
    required this.discount,
    required this.grandTotal,
    required this.paymentMethod,
    required this.cashPaid,
    required this.changeAmount,
    this.customerName,
    this.items = const [],
  });
}
