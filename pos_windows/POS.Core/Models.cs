namespace POS.Core;

public enum PriceLevel { H1, H2, Grosir }

public record User(string Id, string Username, string Password, string Role, bool Active = true);

public record Warehouse(string Id, string Name, string? Address = null);

public record Product(
    string Id,
    string Name,
    string? Barcode = null,
    string? Unit = null,
    string? Sku = null,
    bool Active = true,
    int? PriceH1 = null,
    int? PriceH2 = null,
    int? PriceGrosir = null,
    string? Article = null,
    string? SupplierId = null,
    string? ItemTypeId = null
);

public record Stock(string ProductId, string WarehouseId, int Qty);

public record StockMove(long Id, string ProductId, string WarehouseId, int QtyChange, string Reason, string? RefId, DateTime CreatedAt);

public record SaleItem(string ProductId, string Name, int Qty, int Price, int Discount = 0)
{
    public int Total => Qty * Price - Discount;
}

public record Sale(
    string Id,
    string Number,
    DateTime Date,
    string WarehouseId,
    List<SaleItem> Items,
    int Subtotal,
    int Discount,
    int Tax,
    int Total,
    string? CustomerId = null,
    string? CustomerName = null,
    string? Salesman = null,
    string? PaymentMethod = null
);

// Purchasing & Parties
public record Supplier(string Id, string Name, string? Phone = null, string? Code = null);
public record Customer(string Id, string Name, string? Phone = null, string? MemberCode = null);
public record Salesman(string Id, string Name, string? Phone = null);

public record BankAccount(string Id, string Bank, string Number, string? Holder = null);

public record EdcMachine(string Id, string Name, string? Bank = null, string? Location = null);

public record ItemType(string Id, string Code, string Name);

public record PurchaseItem(string ProductId, string Name, int Qty, int Price, int Discount = 0)
{
    public int Total => Qty * Price - Discount;
}

public record Purchase(string Id, string Number, DateTime Date, string WarehouseId, string SupplierId, string SupplierName, List<PurchaseItem> Items, int Subtotal, int Discount, int Tax, int Total, string PaymentStatus);

public record Payment(string Id, string RefType, string RefId, int Amount, DateTime Date); // RefType: SALE / PURCHASE

public record Receivable(string Id, string SaleId, int Total, int Paid, int Balance);
public record Payable(string Id, string PurchaseId, int Total, int Paid, int Balance);

// Back office
public record Expense(string Id, DateTime Date, string Account, string Description, int Amount);
public record AccountTransfer(string Id, DateTime Date, string FromAccount, string ToAccount, int Amount, string? Note = null);
