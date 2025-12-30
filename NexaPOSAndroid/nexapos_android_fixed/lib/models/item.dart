class Item {
  final int? id;
  final String code;
  final String? barcode;
  final String name;
  final String? category;
  final String unit;
  final int buyPrice;
  final int sellPrice;
  final int sellPriceLv2;
  final int sellPriceLv3;
  final double stock;
  final double minStock;
  final bool isActive;

  Item({
    this.id,
    required this.code,
    this.barcode,
    required this.name,
    this.category,
    this.unit = 'pcs',
    this.buyPrice = 0,
    this.sellPrice = 0,
    this.sellPriceLv2 = 0,
    this.sellPriceLv3 = 0,
    this.stock = 0,
    this.minStock = 0,
    this.isActive = true,
  });

  Map<String, dynamic> toMap() => {
        'id': id,
        'code': code,
        'barcode': barcode,
        'name': name,
        'category': category,
        'unit': unit,
        'buy_price': buyPrice,
        'sell_price': sellPrice,
        'sell_price_lv2': sellPriceLv2,
        'sell_price_lv3': sellPriceLv3,
        'stock': stock,
        'min_stock': minStock,
        'is_active': isActive ? 1 : 0,
      };

  factory Item.fromMap(Map<String, dynamic> m) => Item(
        id: m['id'] as int?,
        code: m['code'] ?? '',
        barcode: m['barcode'],
        name: m['name'] ?? '',
        category: m['category'],
        unit: m['unit'] ?? 'pcs',
        buyPrice: (m['buy_price'] ?? 0) as int,
        sellPrice: (m['sell_price'] ?? 0) as int,
        sellPriceLv2: (m['sell_price_lv2'] ?? 0) as int,
        sellPriceLv3: (m['sell_price_lv3'] ?? 0) as int,
        stock: (m['stock'] ?? 0).toDouble(),
        minStock: (m['min_stock'] ?? 0).toDouble(),
        isActive: (m['is_active'] ?? 1) == 1,
      );
}
