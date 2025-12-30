using System;
using System.Collections.Generic;
using System.Linq;
using Microsoft.EntityFrameworkCore;
using POS.Core;

namespace POS.Storage;

public class EfStorage : IStorage
{
    private readonly PosDbContext _db = new();
    public EfStorage()
    {
        _db.Database.EnsureCreated();
        // seed default warehouse if empty
        if (!_db.Warehouses.Any())
        {
            _db.Warehouses.Add(new WarehouseEntity { Id = "GUDANG-UTAMA", Name = "Gudang Utama" });
            _db.SaveChanges();
        }
    }

    // Item types
    public List<ItemType> ItemTypes()
        => _db.ItemTypes.OrderBy(t => t.Name)
            .Select(t => new ItemType(t.Id, t.Code, t.Name))
            .ToList();

    public void UpsertItemType(ItemType t)
    {
        var e = _db.ItemTypes.Find(t.Id);
        if (e == null) _db.ItemTypes.Add(new ItemTypeEntity { Id = t.Id, Code = t.Code, Name = t.Name });
        else { e.Code = t.Code; e.Name = t.Name; }
        _db.SaveChanges();
    }

    public void DeleteItemType(string id)
    {
        var e = _db.ItemTypes.Find(id);
        if (e != null) { _db.ItemTypes.Remove(e); _db.SaveChanges(); }
    }

    // Suppliers
    public List<Supplier> Suppliers()
        => _db.Suppliers.OrderBy(s => s.Name)
            .Select(s => new Supplier(s.Id, s.Name, s.Phone, s.Code))
            .ToList();

    public void UpsertSupplier(Supplier s)
    {
        var e = _db.Suppliers.Find(s.Id);
        if (e == null) _db.Suppliers.Add(new SupplierEntity { Id = s.Id, Name = s.Name, Phone = s.Phone, Code = s.Code });
        else { e.Name = s.Name; e.Phone = s.Phone; e.Code = s.Code; }
        _db.SaveChanges();
    }

    public void DeleteSupplier(string id)
    {
        var e = _db.Suppliers.Find(id);
        if (e != null) { _db.Suppliers.Remove(e); _db.SaveChanges(); }
    }

    // Products
    public List<Product> Products(string? query = null)
    {
        var q = _db.Products.AsQueryable();
        if (!string.IsNullOrWhiteSpace(query))
        {
            var like = query.ToLowerInvariant();
            q = q.Where(p => p.Name.ToLower().Contains(like) || (p.Barcode ?? "").ToLower() == like || (p.Sku ?? "").ToLower().Contains(like));
        }
        return q.OrderBy(p => p.Name)
            .Select(p => new Product(p.Id, p.Name, p.Barcode, p.Unit, p.Sku, p.Active, p.PriceH1, p.PriceH2, p.PriceGrosir, p.Article, p.SupplierId, p.ItemTypeId))
            .ToList();
    }

    public void UpsertProduct(Product p)
    {
        var e = _db.Products.Find(p.Id);
        if (e == null)
        {
            e = new ProductEntity { Id = p.Id };
            _db.Products.Add(e);
        }
        e.Name = p.Name;
        e.Barcode = p.Barcode;
        e.Unit = p.Unit;
        e.Sku = p.Sku;
        e.Active = p.Active;
        e.PriceH1 = p.PriceH1;
        e.PriceH2 = p.PriceH2;
        e.PriceGrosir = p.PriceGrosir;
        e.Article = p.Article;
        e.SupplierId = p.SupplierId;
        e.ItemTypeId = p.ItemTypeId;
        _db.SaveChanges();
    }

    public void DeleteProduct(string id)
    {
        var e = _db.Products.Find(id);
        if (e != null) { _db.Products.Remove(e); _db.SaveChanges(); }
    }

    public string GenerateProductBarcode(string supplierId, string itemTypeId)
    {
        var sup = _db.Suppliers.Find(supplierId);
        var typ = _db.ItemTypes.Find(itemTypeId);
        var supCode = ((sup?.Code) ?? "01").PadLeft(2, '0');
        var typeCode = ((typ?.Code) ?? "01").PadLeft(2, '0');
        var existing = _db.Products.Where(p => p.SupplierId == supplierId && p.ItemTypeId == itemTypeId);
        int maxSeq = 0;
        foreach (var pr in existing)
        {
            if (!string.IsNullOrWhiteSpace(pr.Barcode) && pr.Barcode!.Length >= 7)
            {
                var suf = pr.Barcode.Substring(pr.Barcode.Length - 3);
                if (int.TryParse(suf, out var n) && n > maxSeq) maxSeq = n;
            }
        }
        var next = Math.Min(maxSeq + 1, 999);
        var seq = next.ToString().PadLeft(3, '0');
        return supCode + typeCode + seq;
    }

    // Warehouses + stock
    public List<Warehouse> Warehouses() => _db.Warehouses
        .OrderBy(w => w.Name)
        .Select(w => new Warehouse(w.Id, w.Name, w.Address))
        .ToList();
    public int GetStock(string productId, string warehouseId)
    {
        var s = _db.Stocks.Find(productId, warehouseId);
        return s?.Qty ?? 0;
    }
    public void SetStock(string productId, string warehouseId, int qty, string reason = "ADJUST")
    {
        var s = _db.Stocks.Find(productId, warehouseId);
        if (s == null) _db.Stocks.Add(new StockEntity { ProductId = productId, WarehouseId = warehouseId, Qty = qty });
        else s.Qty = qty;
        _db.SaveChanges();
    }
    public void TransferStock(string productId, string fromWarehouseId, string toWarehouseId, int qty)
    {
        var from = _db.Stocks.Find(productId, fromWarehouseId);
        if (from == null) from = new StockEntity { ProductId = productId, WarehouseId = fromWarehouseId, Qty = 0 };
        from.Qty = Math.Max(0, from.Qty - qty);
        var to = _db.Stocks.Find(productId, toWarehouseId);
        if (to == null) _db.Stocks.Add(to = new StockEntity { ProductId = productId, WarehouseId = toWarehouseId, Qty = 0 });
        to.Qty += qty;
        if (_db.Entry(from).State == Microsoft.EntityFrameworkCore.EntityState.Detached) _db.Stocks.Add(from);
        _db.SaveChanges();
    }
    public void OpnameSetQuantities(string warehouseId, List<(string productId, int qty)> rows)
    {
        foreach (var (productId, qty) in rows)
        {
            var s = _db.Stocks.Find(productId, warehouseId);
            if (s == null) _db.Stocks.Add(new StockEntity { ProductId = productId, WarehouseId = warehouseId, Qty = qty });
            else s.Qty = qty;
        }
        _db.SaveChanges();
    }
    public bool ReplaceSaleItems(string saleNumber, List<SaleItem> newItems)
    {
        var sale = _db.Sales.FirstOrDefault(s => s.Number == saleNumber);
        if (sale == null) return false;
        // revert old stock
        var oldItems = _db.SaleItems.Where(i => i.SaleId == sale.Id).ToList();
        foreach (var it in oldItems)
        {
            var s = _db.Stocks.Find(it.ProductId, sale.WarehouseId);
            if (s == null) _db.Stocks.Add(new StockEntity { ProductId = it.ProductId, WarehouseId = sale.WarehouseId, Qty = it.Qty });
            else s.Qty += it.Qty;
        }
        _db.SaleItems.RemoveRange(oldItems);
        // apply new items
        foreach (var it in newItems)
        {
            _db.SaleItems.Add(new SaleItemEntity { SaleId = sale.Id, ProductId = it.ProductId, Name = it.Name, Qty = it.Qty, Price = it.Price, Discount = it.Discount });
            var s = _db.Stocks.Find(it.ProductId, sale.WarehouseId);
            if (s == null) _db.Stocks.Add(new StockEntity { ProductId = it.ProductId, WarehouseId = sale.WarehouseId, Qty = 0 });
            else s.Qty = Math.Max(0, s.Qty - it.Qty);
        }
        sale.Subtotal = newItems.Sum(i => i.Total);
        sale.Total = sale.Subtotal - sale.Discount + sale.Tax;
        sale.Date = DateTime.Now;
        _db.SaveChanges();
        return true;
    }
    public bool ReturnPurchaseItems(string purchaseNumber, List<PurchaseItem> retItems)
    {
        var inv = _db.Purchases.FirstOrDefault(p => p.Number == purchaseNumber);
        if (inv == null) return false;
        foreach (var it in retItems)
        {
            var s = _db.Stocks.Find(it.ProductId, inv.WarehouseId) ?? new StockEntity { ProductId = it.ProductId, WarehouseId = inv.WarehouseId, Qty = 0 };
            s.Qty = Math.Max(0, s.Qty - it.Qty);
            if (_db.Entry(s).State == EntityState.Detached) _db.Stocks.Add(s);
        }
        var ret = retItems.Sum(i => i.Total);
        inv.Subtotal = Math.Max(0, inv.Subtotal - ret);
        inv.Total = Math.Max(0, inv.Total - ret);
        _db.SaveChanges();
        return true;
    }
    public void AddExpense(DateTime date, string account, string description, int amount)
    {
        _db.Expenses.Add(new ExpenseEntity { Id = Guid.NewGuid().ToString("N"), Date = date, Account = account, Description = description, Amount = amount });
        _db.SaveChanges();
    }
    public List<Expense> ListExpenses(DateTime? from = null, DateTime? to = null)
    {
        var q = _db.Expenses.AsQueryable();
        if (from.HasValue) q = q.Where(x => x.Date >= from.Value);
        if (to.HasValue) q = q.Where(x => x.Date <= to.Value);
        return q.OrderByDescending(x => x.Date).Select(x => new Expense(x.Id, x.Date, x.Account, x.Description ?? "", x.Amount)).ToList();
    }

    public void DeleteExpense(string id)
    {
        var e = _db.Expenses.Find(id);
        if (e != null) { _db.Expenses.Remove(e); _db.SaveChanges(); }
    }
    public void AddAccountTransfer(DateTime date, string fromAccount, string toAccount, int amount, string? note = null)
    {
        _db.AccountTransfers.Add(new AccountTransferEntity { Id = Guid.NewGuid().ToString("N"), Date = date, FromAccount = fromAccount, ToAccount = toAccount, Amount = amount, Note = note });
        _db.SaveChanges();
    }
    public List<AccountTransfer> ListAccountTransfers(DateTime? from = null, DateTime? to = null)
    {
        var q = _db.AccountTransfers.AsQueryable();
        if (from.HasValue) q = q.Where(x => x.Date >= from.Value);
        if (to.HasValue) q = q.Where(x => x.Date <= to.Value);
        return q.OrderByDescending(x => x.Date).Select(x => new AccountTransfer(x.Id, x.Date, x.FromAccount, x.ToAccount, x.Amount, x.Note)).ToList();
    }

    public void DeleteAccountTransfer(string id)
    {
        var e = _db.AccountTransfers.Find(id);
        if (e != null) { _db.AccountTransfers.Remove(e); _db.SaveChanges(); }
    }
    public string CreateSale(string warehouseId, List<SaleItem> items, int discount = 0, int tax = 0, string? customerId = null, string? customerName = null, string? salesman = null, string? paymentMethod = null)
    {
        var today = DateTime.Now;
        var prefix = $"SI-{today:yyyyMMdd}-";
        var last = _db.Sales.Where(s => s.Number.StartsWith(prefix)).OrderByDescending(s => s.Number).FirstOrDefault();
        var seq = 1;
        if (last != null)
        {
            var tail = last.Number.Substring(prefix.Length);
            if (int.TryParse(tail, out var n)) seq = n + 1;
        }
        var number = $"{prefix}{seq:0000}";
        var subtotal = items.Sum(i => i.Total);
        var total = subtotal - discount + tax;
        var id = Guid.NewGuid().ToString("N");
        _db.Sales.Add(new SaleEntity { Id = id, Number = number, Date = today, WarehouseId = warehouseId, Subtotal = subtotal, Discount = discount, Tax = tax, Total = total, CustomerId = customerId, CustomerName = customerName, Salesman = salesman, PaymentMethod = paymentMethod });
        foreach (var it in items)
        {
            _db.SaleItems.Add(new SaleItemEntity { SaleId = id, ProductId = it.ProductId, Name = it.Name, Qty = it.Qty, Price = it.Price, Discount = it.Discount });
            var s = _db.Stocks.Find(it.ProductId, warehouseId) ?? new StockEntity { ProductId = it.ProductId, WarehouseId = warehouseId, Qty = 0 };
            s.Qty = Math.Max(0, s.Qty - it.Qty);
            if (_db.Entry(s).State == EntityState.Detached) _db.Stocks.Add(s);
        }
        _db.SaveChanges();
        return number;
    }
    public string CreatePurchase(string warehouseId, string supplierName, List<PurchaseItem> items, int discount = 0, int tax = 0, int payNow = 0)
    {
        var supplier = _db.Suppliers.FirstOrDefault(s => s.Name == supplierName);
        if (supplier == null) { supplier = new SupplierEntity { Id = Guid.NewGuid().ToString("N"), Name = supplierName }; _db.Suppliers.Add(supplier); }
        var today = DateTime.Now;
        var prefix = $"PI-{today:yyyyMMdd}-";
        var last = _db.Purchases.Where(p => p.Number.StartsWith(prefix)).OrderByDescending(p => p.Number).FirstOrDefault();
        var seq = 1; if (last != null) { var tail = last.Number.Substring(prefix.Length); if (int.TryParse(tail, out var n)) seq = n + 1; }
        var number = $"{prefix}{seq:0000}";
        var subtotal = items.Sum(i => i.Total); var total = subtotal - discount + tax;
        var id = Guid.NewGuid().ToString("N");
        _db.Purchases.Add(new PurchaseEntity { Id = id, Number = number, Date = today, WarehouseId = warehouseId, SupplierId = supplier.Id, SupplierName = supplier.Name, Subtotal = subtotal, Discount = discount, Tax = tax, Total = total, PaymentStatus = payNow >= total ? "PAID" : (payNow > 0 ? "PARTIAL" : "UNPAID") });
        foreach (var it in items)
        {
            _db.PurchaseItems.Add(new PurchaseItemEntity { PurchaseId = id, ProductId = it.ProductId, Name = it.Name, Qty = it.Qty, Price = it.Price, Discount = it.Discount });
            var s = _db.Stocks.Find(it.ProductId, warehouseId) ?? new StockEntity { ProductId = it.ProductId, WarehouseId = warehouseId, Qty = 0 };
            s.Qty += it.Qty;
            if (_db.Entry(s).State == EntityState.Detached) _db.Stocks.Add(s);
        }
        if (payNow > 0) _db.Payments.Add(new PaymentEntity { Id = Guid.NewGuid().ToString("N"), RefType = "PURCHASE", RefId = id, Amount = payNow, Date = today });
        var bal = total - payNow; if (bal > 0) _db.Payables.Add(new PayableEntity { Id = Guid.NewGuid().ToString("N"), PurchaseId = id, Total = total, Paid = payNow, Balance = bal });
        _db.SaveChanges();
        return number;
    }
    public List<Sale> LoadSales()
    {
        var sales = _db.Sales.OrderByDescending(s => s.Date).ToList();
        var result = new List<Sale>();
        foreach (var s in sales)
        {
            var items = _db.SaleItems.Where(i => i.SaleId == s.Id)
                .Select(i => new SaleItem(i.ProductId, i.Name, i.Qty, i.Price, i.Discount)).ToList();
            result.Add(new Sale(s.Id, s.Number, s.Date, s.WarehouseId, items, s.Subtotal, s.Discount, s.Tax, s.Total, s.CustomerId, s.CustomerName, s.Salesman, s.PaymentMethod));
        }
        return result;
    }
    public void DeleteSale(string saleId)
    {
        var sale = _db.Sales.Find(saleId); if (sale == null) return;
        var items = _db.SaleItems.Where(i => i.SaleId == saleId).ToList();
        foreach (var it in items)
        {
            var s = _db.Stocks.Find(it.ProductId, sale.WarehouseId) ?? new StockEntity { ProductId = it.ProductId, WarehouseId = sale.WarehouseId, Qty = 0 };
            s.Qty += it.Qty;
            if (_db.Entry(s).State == EntityState.Detached) _db.Stocks.Add(s);
        }
        _db.SaleItems.RemoveRange(items); _db.Sales.Remove(sale); _db.SaveChanges();
    }
    public List<Purchase> LoadPurchases()
    {
        var prs = _db.Purchases.OrderByDescending(p => p.Date).ToList();
        var result = new List<Purchase>();
        foreach (var p in prs)
        {
            var items = _db.PurchaseItems.Where(i => i.PurchaseId == p.Id)
                .Select(i => new PurchaseItem(i.ProductId, i.Name, i.Qty, i.Price, i.Discount)).ToList();
            result.Add(new Purchase(p.Id, p.Number, p.Date, p.WarehouseId, p.SupplierId, p.SupplierName, items, p.Subtotal, p.Discount, p.Tax, p.Total, p.PaymentStatus));
        }
        return result;
    }

    public void DeletePurchase(string purchaseId)
    {
        var purchase = _db.Purchases.Find(purchaseId);
        if (purchase == null) return;
        var items = _db.PurchaseItems.Where(i => i.PurchaseId == purchaseId).ToList();
        _db.PurchaseItems.RemoveRange(items);
        _db.Purchases.Remove(purchase);
        _db.SaveChanges();
    }
    public List<Payable> Payables()
        => _db.Payables.Where(p => p.Balance > 0).Select(p => new Payable(p.Id, p.PurchaseId, p.Total, p.Paid, p.Balance)).ToList();
    public void PayPurchase(string purchaseId, int amount)
    {
        var pay = _db.Payables.FirstOrDefault(p => p.PurchaseId == purchaseId); if (pay == null) return;
        pay.Paid += amount; pay.Balance = Math.Max(0, pay.Total - pay.Paid);
        _db.Payments.Add(new PaymentEntity { Id = Guid.NewGuid().ToString("N"), RefType = "PURCHASE", RefId = purchaseId, Amount = amount, Date = DateTime.Now });
        var inv = _db.Purchases.Find(purchaseId); if (inv != null) inv.PaymentStatus = pay.Balance == 0 ? "PAID" : "PARTIAL";
        _db.SaveChanges();
    }
    public List<Customer> Customers() => _db.Customers
        .OrderBy(c => c.Name)
        .Select(c => new Customer(c.Id, c.Name, c.Phone, c.MemberCode))
        .ToList();
    public void UpsertCustomer(Customer c)
    {
        var e = _db.Customers.Find(c.Id);
        if (e == null) _db.Customers.Add(new CustomerEntity { Id = c.Id, Name = c.Name, Phone = c.Phone, MemberCode = c.MemberCode });
        else { e.Name = c.Name; e.Phone = c.Phone; e.MemberCode = c.MemberCode; }
        _db.SaveChanges();
    }

    public void DeleteCustomer(string id)
    {
        var e = _db.Customers.Find(id);
        if (e != null) { _db.Customers.Remove(e); _db.SaveChanges(); }
    }

    // Salesmen
    public List<Salesman> Salesmen() => _db.Salesmen
        .OrderBy(s => s.Name)
        .Select(s => new Salesman(s.Id, s.Name, s.Phone))
        .ToList();
    public void UpsertSalesman(Salesman s)
    {
        var e = _db.Salesmen.Find(s.Id);
        if (e == null) _db.Salesmen.Add(new SalesmanEntity { Id = s.Id, Name = s.Name, Phone = s.Phone });
        else { e.Name = s.Name; e.Phone = s.Phone; }
        _db.SaveChanges();
    }
    public void DeleteSalesman(string id)
    {
        var e = _db.Salesmen.Find(id);
        if (e != null) { _db.Salesmen.Remove(e); _db.SaveChanges(); }
    }

    // Bank Accounts
    public List<BankAccount> BankAccounts() => _db.BankAccounts.Select(b => new BankAccount(b.Id, b.Bank, b.Number, b.Holder)).OrderBy(b => b.Bank).ToList();
    public void UpsertBankAccount(BankAccount ba)
    {
        var e = _db.BankAccounts.Find(ba.Id);
        if (e == null) _db.BankAccounts.Add(new BankAccountEntity { Id = ba.Id, Bank = ba.Bank, Number = ba.Number, Holder = ba.Holder });
        else { e.Bank = ba.Bank; e.Number = ba.Number; e.Holder = ba.Holder; }
        _db.SaveChanges();
    }
    public void DeleteBankAccount(string id)
    {
        var e = _db.BankAccounts.Find(id);
        if (e != null) { _db.BankAccounts.Remove(e); _db.SaveChanges(); }
    }

    // EDC Machines
    public List<EdcMachine> EdcMachines() => _db.EdcMachines.Select(em => new EdcMachine(em.Id, em.Name, em.Bank, em.Location)).OrderBy(em => em.Name).ToList();
    public void UpsertEdcMachine(EdcMachine em)
    {
        var e = _db.EdcMachines.Find(em.Id);
        if (e == null) _db.EdcMachines.Add(new EdcMachineEntity { Id = em.Id, Name = em.Name, Bank = em.Bank, Location = em.Location });
        else { e.Name = em.Name; e.Bank = em.Bank; e.Location = em.Location; }
        _db.SaveChanges();
    }
    public void DeleteEdcMachine(string id)
    {
        var e = _db.EdcMachines.Find(id);
        if (e != null) { _db.EdcMachines.Remove(e); _db.SaveChanges(); }
    }
    public string? GetSetting(string key)
        => _db.AppSettings.Find(key)?.Value;
    public void SetSetting(string key, string value)
    {
        var s = _db.AppSettings.Find(key);
        if (s == null) _db.AppSettings.Add(new AppSettingEntity { Key = key, Value = value });
        else s.Value = value;
        _db.SaveChanges();
    }
    public (int subtotal, int tax, int total) SalesSummary(DateTime from, DateTime to)
    {
        var s = _db.Sales.Where(x => x.Date >= from && x.Date <= to);
        return (s.Sum(x => x.Subtotal), s.Sum(x => x.Tax), s.Sum(x => x.Total));
    }
    public List<(string Name, int Total)> SalesBySalesman(DateTime from, DateTime to)
        => _db.Sales.Where(x => x.Date >= from && x.Date <= to)
            .GroupBy(x => x.Salesman ?? "-")
            .Select(g => new { Name = g.Key, Total = g.Sum(x => x.Total) })
            .AsEnumerable()
            .Select(x => (x.Name, x.Total)).ToList();
    public List<(string Name, int Total)> SalesBySupplier(DateTime from, DateTime to)
    {
        var last = _db.Purchases.Join(_db.PurchaseItems,
            p => p.Id, i => i.PurchaseId,
            (p, i) => new { p.SupplierName, i.ProductId, p.Date })
            .AsEnumerable()
            .GroupBy(x => x.ProductId)
            .ToDictionary(g => g.Key, g => g.OrderByDescending(x => x.Date).First().SupplierName);
        var map = new Dictionary<string, int>();
        var sales = _db.Sales.Where(s => s.Date >= from && s.Date <= to).Select(s => s.Id).ToList();
        var items = _db.SaleItems.Where(i => sales.Contains(i.SaleId)).ToList();
        foreach (var it in items)
        {
            var sup = last.TryGetValue(it.ProductId, out var v) ? v : "-";
            map[sup] = map.TryGetValue(sup, out var cur) ? cur + (it.Qty * it.Price - it.Discount) : (it.Qty * it.Price - it.Discount);
        }
        return map.Select(kv => (kv.Key, kv.Value)).ToList();
    }

    public (int sales, int expenses) ProfitLossSummary(DateTime from, DateTime to)
    {
        var sales = _db.Sales.Where(s => s.Date >= from && s.Date <= to).Sum(s => s.Total);
        var expenses = _db.Expenses.Where(e => e.Date >= from && e.Date <= to).Sum(e => e.Amount);
        return (sales, expenses);
    }
}
