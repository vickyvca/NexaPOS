namespace POS.Storage;

public class ProductEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Barcode { get; set; }
    public string? Unit { get; set; }
    public string? Sku { get; set; }
    public bool Active { get; set; } = true;
    public int? PriceH1 { get; set; }
    public int? PriceH2 { get; set; }
    public int? PriceGrosir { get; set; }
    public string? Article { get; set; }
    public string? SupplierId { get; set; }
    public string? ItemTypeId { get; set; }
}

public class SupplierEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Phone { get; set; }
    public string? Code { get; set; }
}

public class ItemTypeEntity
{
    public string Id { get; set; } = string.Empty;
    public string Code { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
}

public class WarehouseEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Address { get; set; }
}

public class StockEntity
{
    public string ProductId { get; set; } = string.Empty;
    public string WarehouseId { get; set; } = string.Empty;
    public int Qty { get; set; }
}

public class AppSettingEntity
{
    public string Key { get; set; } = string.Empty;
    public string? Value { get; set; }
}

public class CustomerEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Phone { get; set; }
    public string? MemberCode { get; set; }
}

public class SalesmanEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Phone { get; set; }
}

public class BankAccountEntity
{
    public string Id { get; set; } = string.Empty;
    public string Bank { get; set; } = string.Empty;
    public string Number { get; set; } = string.Empty;
    public string? Holder { get; set; }
}

public class EdcMachineEntity
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string? Bank { get; set; }
    public string? Location { get; set; }
}

public class SaleEntity
{
    public string Id { get; set; } = string.Empty;
    public string Number { get; set; } = string.Empty;
    public DateTime Date { get; set; }
    public string WarehouseId { get; set; } = string.Empty;
    public int Subtotal { get; set; }
    public int Discount { get; set; }
    public int Tax { get; set; }
    public int Total { get; set; }
    public string? CustomerId { get; set; }
    public string? CustomerName { get; set; }
    public string? Salesman { get; set; }
    public string? PaymentMethod { get; set; }
}

public class SaleItemEntity
{
    public int Id { get; set; }
    public string SaleId { get; set; } = string.Empty;
    public string ProductId { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public int Qty { get; set; }
    public int Price { get; set; }
    public int Discount { get; set; }
}

public class PurchaseEntity
{
    public string Id { get; set; } = string.Empty;
    public string Number { get; set; } = string.Empty;
    public DateTime Date { get; set; }
    public string WarehouseId { get; set; } = string.Empty;
    public string SupplierId { get; set; } = string.Empty;
    public string SupplierName { get; set; } = string.Empty;
    public int Subtotal { get; set; }
    public int Discount { get; set; }
    public int Tax { get; set; }
    public int Total { get; set; }
    public string PaymentStatus { get; set; } = "UNPAID";
}

public class PurchaseItemEntity
{
    public int Id { get; set; }
    public string PurchaseId { get; set; } = string.Empty;
    public string ProductId { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public int Qty { get; set; }
    public int Price { get; set; }
    public int Discount { get; set; }
}

public class PaymentEntity
{
    public string Id { get; set; } = string.Empty;
    public string RefType { get; set; } = string.Empty; // PURCHASE / SALE
    public string RefId { get; set; } = string.Empty;
    public int Amount { get; set; }
    public DateTime Date { get; set; }
}

public class PayableEntity
{
    public string Id { get; set; } = string.Empty;
    public string PurchaseId { get; set; } = string.Empty;
    public int Total { get; set; }
    public int Paid { get; set; }
    public int Balance { get; set; }
}

public class ExpenseEntity
{
    public string Id { get; set; } = string.Empty;
    public DateTime Date { get; set; }
    public string Account { get; set; } = string.Empty;
    public string? Description { get; set; }
    public int Amount { get; set; }
}

public class AccountTransferEntity
{
    public string Id { get; set; } = string.Empty;
    public DateTime Date { get; set; }
    public string FromAccount { get; set; } = string.Empty;
    public string ToAccount { get; set; } = string.Empty;
    public int Amount { get; set; }
    public string? Note { get; set; }
}
