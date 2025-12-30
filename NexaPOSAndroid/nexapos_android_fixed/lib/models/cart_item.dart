class CartItem {
  int? id;
  String name;
  int price;
  double qty;
  int discount;
  int level;
  int? batchId;
  String? batchNo;
  String? expiry;

  CartItem({
    this.id,
    required this.name,
    required this.price,
    this.qty = 1,
    this.discount = 0,
    this.level = 1,
    this.batchId,
    this.batchNo,
    this.expiry,
  });

  int get subtotal => (price * qty).round() - discount;

  Map<String, dynamic> toMap() => {
        'id': id,
        'name': name,
        'price': price,
        'qty': qty,
        'discount': discount,
        'level': level,
        'batch_id': batchId,
        'batch_no': batchNo,
        'expiry': expiry,
      };

  factory CartItem.fromMap(Map<String, dynamic> m) => CartItem(
        id: m['id'],
        name: m['name'],
        price: m['price'],
        qty: (m['qty'] as num).toDouble(),
        discount: m['discount'] ?? 0,
        level: m['level'] ?? 1,
        batchId: m['batch_id'],
        batchNo: m['batch_no'],
        expiry: m['expiry'],
      );

  CartItem copyWith({
    int? id,
    String? name,
    int? price,
    double? qty,
    int? discount,
    int? level,
    int? batchId,
    String? batchNo,
    String? expiry,
  }) {
    return CartItem(
      id: id ?? this.id,
      name: name ?? this.name,
      price: price ?? this.price,
      qty: qty ?? this.qty,
      discount: discount ?? this.discount,
      level: level ?? this.level,
      batchId: batchId ?? this.batchId,
      batchNo: batchNo ?? this.batchNo,
      expiry: expiry ?? this.expiry,
    );
  }
}
