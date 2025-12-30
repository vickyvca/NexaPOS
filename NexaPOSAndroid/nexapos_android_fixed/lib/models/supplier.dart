class Supplier {
  final int? id;
  final String name;
  final String? phone;
  final String? address;

  Supplier({this.id, required this.name, this.phone, this.address});

  Map<String, dynamic> toMap() => {
        'id': id,
        'name': name,
        'phone': phone,
        'address': address,
      };

  factory Supplier.fromMap(Map<String, dynamic> m) => Supplier(
        id: m['id'] as int?,
        name: m['name'] ?? '',
        phone: m['phone'],
        address: m['address'],
      );
}
