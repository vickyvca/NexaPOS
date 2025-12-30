class Product {
  final String id;
  final String name;
  final String? barcode;
  final String? unit;
  final String? sku;
  final bool active;

  const Product({
    required this.id,
    required this.name,
    this.barcode,
    this.unit,
    this.sku,
    this.active = true,
  });

  Product copyWith({
    String? id,
    String? name,
    String? barcode,
    String? unit,
    String? sku,
    bool? active,
  }) => Product(
        id: id ?? this.id,
        name: name ?? this.name,
        barcode: barcode ?? this.barcode,
        unit: unit ?? this.unit,
        sku: sku ?? this.sku,
        active: active ?? this.active,
      );
}

enum PriceLevel { h1, h2, grosir }
