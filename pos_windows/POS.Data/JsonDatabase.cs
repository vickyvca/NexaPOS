using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text.Json;
using POS.Core;

namespace POS.Data;

public sealed class JsonDatabase
{
    private readonly string _path;
    private readonly object _gate = new();

    public JsonDatabase(string? path = null)
    {
        var appData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
        var dir = Path.Combine(appData, "POSWindows");
        Directory.CreateDirectory(dir);
        _path = path ?? Path.Combine(dir, "pos.json");
    }

    private class DbModel
    {
        public List<User> Users { get; set; } = new();
        public List<Warehouse> Warehouses { get; set; } = new();
        public List<Product> Products { get; set; } = new();
        public List<Stock> Stocks { get; set; } = new();
        public List<StockMove> StockMoves { get; set; } = new();
        public List<Sale> Sales { get; set; } = new();
        public Dictionary<string,string> Settings { get; set; } = new();
        public List<Supplier> Suppliers { get; set; } = new();
        public List<Customer> Customers { get; set; } = new();
        public List<Salesman> Salesmen { get; set; } = new();
        public List<BankAccount> BankAccounts { get; set; } = new();
        public List<EdcMachine> EdcMachines { get; set; } = new();
        public List<Purchase> Purchases { get; set; } = new();
        public List<Payment> Payments { get; set; } = new();
        public List<Receivable> Receivables { get; set; } = new();
        public List<Payable> Payables { get; set; } = new();
        public List<Expense> Expenses { get; set; } = new();
        public List<AccountTransfer> AccountTransfers { get; set; } = new();
        public List<ItemType> ItemTypes { get; set; } = new();
    }

    private DbModel Load()
    {
        lock (_gate)
        {
            try
            {
                if (!File.Exists(_path) || new FileInfo(_path).Length == 0)
                    return Seed(new DbModel());

                var json = File.ReadAllText(_path);
                var db = JsonSerializer.Deserialize<DbModel>(json, new JsonSerializerOptions { PropertyNameCaseInsensitive = true }) ?? new DbModel();
                // Ensure minimum seed exists
                EnsureSeed(db);
                return db;
            }
            catch
            {
                // Backup corrupt file then reseed
                try
                {
                    if (File.Exists(_path))
                    {
                        var bak = _path + ".bak";
                        File.Copy(_path, bak, overwrite: true);
                    }
                }
                catch { /* ignore backup errors */ }
                return Seed(new DbModel());
            }
        }
    }

    private void EnsureSeed(DbModel db)
    {
        bool changed = false;
        if (!db.Users.Any()) { db.Users.Add(new User("ADMIN", "admin", "admin", "admin", true)); changed = true; }
        if (!db.Warehouses.Any()) { db.Warehouses.Add(new Warehouse("GUDANG-UTAMA", "Gudang Utama")); changed = true; }
        if (changed) Save(db);
    }

    private DbModel Seed(DbModel db)
    {
        db.Users.Add(new User("ADMIN", "admin", "admin", "admin", true));
        db.Warehouses.Add(new Warehouse("GUDANG-UTAMA", "Gudang Utama"));
        Save(db);
        return db;
    }

    private void Save(DbModel db)
    {
        lock (_gate)
        {
            var json = JsonSerializer.Serialize(db, new JsonSerializerOptions{WriteIndented = true});
            File.WriteAllText(_path, json);
        }
    }

    public bool Login(string username, string password)
    {
        var db = Load();
        return db.Users.Any(u => u.Username == username && u.Password == password && u.Active);
    }

    public List<Warehouse> Warehouses() => Load().Warehouses.ToList();
    public List<ItemType> ItemTypes() => Load().ItemTypes.ToList();
    public List<Purchase> LoadPurchases() => Load().Purchases.ToList();
    public List<Sale> LoadSales() => Load().Sales.ToList();

    public void DeleteSale(string saleId)
    {
        var db = Load();
        var sale = db.Sales.FirstOrDefault(s => s.Id == saleId);
        if (sale is null) return;
        // revert stock
        foreach (var it in sale.Items)
        {
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == sale.WarehouseId);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, sale.WarehouseId, it.Qty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = existing.Qty + it.Qty }); }
        }
        // remove stock moves for this sale
        db.StockMoves.RemoveAll(m => m.RefId == saleId && m.Reason == "SALE");
        db.Sales.Remove(sale);
        Save(db);
    }

    public void DeletePurchase(string purchaseId)
    {
        var db = Load();
        var inv = db.Purchases.FirstOrDefault(p => p.Id == purchaseId);
        if (inv is null) return;
        foreach (var it in inv.Items)
        {
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == inv.WarehouseId);
            var current = existing?.Qty ?? 0;
            var newQty = Math.Max(0, current - it.Qty);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, inv.WarehouseId, newQty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = newQty }); }
        }
        db.StockMoves.RemoveAll(m => m.RefId == purchaseId && m.Reason == "PURCHASE");
        // remove payables and related payments
        var payable = db.Payables.FirstOrDefault(p => p.PurchaseId == purchaseId);
        if (payable != null) db.Payables.Remove(payable);
        db.Payments.RemoveAll(p => p.RefType == "PURCHASE" && p.RefId == purchaseId);
        db.Purchases.Remove(inv);
        Save(db);
    }

    public List<Product> Products(string? query=null)
    {
        var db = Load();
        IEnumerable<Product> q = db.Products;
        if (!string.IsNullOrWhiteSpace(query))
        {
            q = q.Where(p => (p.Name?.Contains(query!, StringComparison.OrdinalIgnoreCase) ?? false)
                           || (p.Barcode?.Equals(query!, StringComparison.OrdinalIgnoreCase) ?? false)
                           || (p.Sku?.Contains(query!, StringComparison.OrdinalIgnoreCase) ?? false));
        }
        return q.OrderBy(p => p.Name).ToList();
    }

    public void UpsertProduct(Product p)
    {
        var db = Load();
        var idx = db.Products.FindIndex(x => x.Id == p.Id);
        if (idx >= 0) db.Products[idx] = p; else db.Products.Add(p);
        Save(db);
    }

    public void DeleteProduct(string id)
    {
        var db = Load();
        db.Products.RemoveAll(p => p.Id == id);
        Save(db);
    }

    public int GetStock(string productId, string warehouseId)
    {
        var db = Load();
        return db.Stocks.FirstOrDefault(s => s.ProductId == productId && s.WarehouseId == warehouseId)?.Qty ?? 0;
    }

    public void SetStock(string productId, string warehouseId, int qty, string reason = "ADJUST")
    {
        var db = Load();
        var s = db.Stocks.FirstOrDefault(x => x.ProductId == productId && x.WarehouseId == warehouseId);
        var prev = s?.Qty ?? 0;
        if (s is null)
        {
            db.Stocks.Add(new Stock(productId, warehouseId, qty));
        }
        else
        {
            db.Stocks.Remove(s);
            db.Stocks.Add(s with { Qty = qty });
        }
        var change = qty - prev;
        db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), productId, warehouseId, change, reason, null, DateTime.UtcNow));
        Save(db);
    }

    public string CreateSale(string warehouseId, List<SaleItem> items, int discount = 0, int tax = 0, string? customerId = null, string? customerName = null, string? salesman = null, string? paymentMethod = null)
    {
        var db = Load();
        var subtotal = items.Sum(i => i.Total);
        var total = subtotal - discount + tax;
        var today = DateTime.Now;
        var prefix = $"SI-{today:yyyyMMdd}-";
        var last = db.Sales.Where(s => s.Number.StartsWith(prefix)).OrderByDescending(s => s.Number).FirstOrDefault();
        var seq = 1;
        if (last != null)
        {
            var tail = last.Number.Substring(prefix.Length);
            if (int.TryParse(tail, out var n)) seq = n + 1;
        }
        var number = $"{prefix}{seq:0000}";
        var id = Guid.NewGuid().ToString("N");
        var sale = new Sale(id, number, today, warehouseId, items, subtotal, discount, tax, total, customerId, customerName, salesman, paymentMethod);
        db.Sales.Add(sale);
        foreach (var it in items)
        {
            var current = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == warehouseId)?.Qty ?? 0;
            var newQty = current - it.Qty;
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == warehouseId);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, warehouseId, newQty));
            else
            {
                db.Stocks.Remove(existing);
                db.Stocks.Add(existing with { Qty = newQty });
            }
            db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), it.ProductId, warehouseId, -it.Qty, "SALE", id, DateTime.UtcNow));
        }
        Save(db);
        return number;
    }

    public string? GetSetting(string key) => Load().Settings.TryGetValue(key, out var v) ? v : null;
    public void SetSetting(string key, string value)
    {
        var db = Load();
        db.Settings[key] = value;
        Save(db);
    }

    // Sales returns: replace items based on invoice number
    public bool ReplaceSaleItems(string saleNumber, List<SaleItem> newItems)
    {
        var db = Load();
        var sale = db.Sales.FirstOrDefault(s => s.Number.Equals(saleNumber, StringComparison.OrdinalIgnoreCase));
        if (sale is null) return false;
        // revert old stock
        foreach (var it in sale.Items)
        {
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == sale.WarehouseId);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, sale.WarehouseId, it.Qty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = existing.Qty + it.Qty }); }
            db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), it.ProductId, sale.WarehouseId, it.Qty, "SALE_RETURN", sale.Id, DateTime.UtcNow));
        }
        // apply new items
        foreach (var it in newItems)
        {
            var current = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == sale.WarehouseId)?.Qty ?? 0;
            var newQty = current - it.Qty;
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == sale.WarehouseId);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, sale.WarehouseId, newQty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = newQty }); }
            db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), it.ProductId, sale.WarehouseId, -it.Qty, "SALE", sale.Id, DateTime.UtcNow));
        }
        var subtotal = newItems.Sum(i => i.Total);
        var tax = sale.Tax; // keep old tax as is; or recompute outside
        var total = subtotal - sale.Discount + tax;
        var replaced = sale with { Items = newItems, Subtotal = subtotal, Total = total, Date = DateTime.Now };
        db.Sales.Remove(sale);
        db.Sales.Add(replaced);
        Save(db);
        return true;
    }

    public bool ReturnPurchaseItems(string purchaseNumber, List<PurchaseItem> retItems)
    {
        var db = Load();
        var inv = db.Purchases.FirstOrDefault(p => p.Number.Equals(purchaseNumber, StringComparison.OrdinalIgnoreCase));
        if (inv is null) return false;
        foreach (var it in retItems)
        {
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == inv.WarehouseId);
            var current = existing?.Qty ?? 0;
            var newQty = Math.Max(0, current - it.Qty);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, inv.WarehouseId, newQty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = newQty }); }
            db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), it.ProductId, inv.WarehouseId, -it.Qty, "PURCHASE_RETURN", inv.Id, DateTime.UtcNow));
        }
        var retSum = retItems.Sum(i => i.Total);
        var newTotal = Math.Max(0, inv.Total - retSum);
        var updated = inv with { Total = newTotal, Subtotal = Math.Max(0, inv.Subtotal - retSum) };
        db.Purchases.Remove(inv);
        db.Purchases.Add(updated);
        Save(db);
        return true;
    }

    public void TransferStock(string productId, string fromWarehouseId, string toWarehouseId, int qty)
    {
        var db = Load();
        var refId = Guid.NewGuid().ToString("N");
        // out
        var fromStock = db.Stocks.FirstOrDefault(x => x.ProductId == productId && x.WarehouseId == fromWarehouseId);
        var fromQty = (fromStock?.Qty ?? 0) - qty;
        if (fromStock is null) db.Stocks.Add(new Stock(productId, fromWarehouseId, Math.Max(0, fromQty)));
        else
        {
            db.Stocks.Remove(fromStock);
            db.Stocks.Add(fromStock with { Qty = Math.Max(0, fromQty) });
        }
        db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), productId, fromWarehouseId, -qty, "TRANSFER_OUT", refId, DateTime.UtcNow));
        // in
        var toStock = db.Stocks.FirstOrDefault(x => x.ProductId == productId && x.WarehouseId == toWarehouseId);
        var toQty = (toStock?.Qty ?? 0) + qty;
        if (toStock is null) db.Stocks.Add(new Stock(productId, toWarehouseId, toQty));
        else { db.Stocks.Remove(toStock); db.Stocks.Add(toStock with { Qty = toQty }); }
        db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), productId, toWarehouseId, qty, "TRANSFER_IN", refId, DateTime.UtcNow));
        Save(db);
    }

    public void OpnameSetQuantities(string warehouseId, List<(string productId, int qty)> rows)
    {
        var db = Load();
        foreach (var (productId, qty) in rows)
        {
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == productId && x.WarehouseId == warehouseId);
            var prev = existing?.Qty ?? 0;
            if (existing is null) db.Stocks.Add(new Stock(productId, warehouseId, qty));
            else { db.Stocks.Remove(existing); db.Stocks.Add(existing with { Qty = qty }); }
            var change = qty - prev;
            if (change != 0)
            {
                db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), productId, warehouseId, change, "OPNAME", null, DateTime.UtcNow));
            }
        }
        Save(db);
    }

    // Back office
    public void AddExpense(DateTime date, string account, string description, int amount)
    {
        var db = Load();
        db.Expenses.Add(new Expense(Guid.NewGuid().ToString("N"), date, account, description, amount));
        Save(db);
    }
    public List<Expense> ListExpenses(DateTime? from = null, DateTime? to = null)
    {
        var db = Load();
        var q = db.Expenses.AsEnumerable();
        if (from.HasValue) q = q.Where(x => x.Date >= from.Value);
        if (to.HasValue) q = q.Where(x => x.Date <= to.Value);
        return q.OrderByDescending(x => x.Date).ToList();
    }

    public void DeleteExpense(string id)
    {
        var db = Load();
        db.Expenses.RemoveAll(e => e.Id == id);
        Save(db);
    }
    public void AddAccountTransfer(DateTime date, string fromAccount, string toAccount, int amount, string? note = null)
    {
        var db = Load();
        db.AccountTransfers.Add(new AccountTransfer(Guid.NewGuid().ToString("N"), date, fromAccount, toAccount, amount, note));
        Save(db);
    }
    public List<AccountTransfer> ListAccountTransfers(DateTime? from = null, DateTime? to = null)
    {
        var db = Load();
        var q = db.AccountTransfers.AsEnumerable();
        if (from.HasValue) q = q.Where(x => x.Date >= from.Value);
        if (to.HasValue) q = q.Where(x => x.Date <= to.Value);
        return q.OrderByDescending(x => x.Date).ToList();
    }

    public void DeleteAccountTransfer(string id)
    {
        var db = Load();
        db.AccountTransfers.RemoveAll(at => at.Id == id);
        Save(db);
    }

    // Reports (basic aggregations)
    public (int subtotal, int tax, int total) SalesSummary(DateTime from, DateTime to)
    {
        var db = Load();
        var s = db.Sales.Where(x => x.Date >= from && x.Date <= to);
        return (s.Sum(x => x.Subtotal), s.Sum(x => x.Tax), s.Sum(x => x.Total));
    }
    public List<(string salesman, int total)> SalesBySalesman(DateTime from, DateTime to)
    {
        var db = Load();
        return db.Sales.Where(x => x.Date >= from && x.Date <= to)
            .GroupBy(x => x.Salesman ?? "-")
            .Select(g => (g.Key, g.Sum(x => x.Total)))
            .OrderByDescending(x => x.Item2)
            .ToList();
    }
    public List<(string supplier, int total)> SalesBySupplier(DateTime from, DateTime to)
    {
        var db = Load();
        // Approx: join by latest purchase supplier per product (simplification)
        var lastSupplier = db.Purchases
            .SelectMany(p => p.Items.Select(i => new { p.SupplierName, i.ProductId, p.Date }))
            .GroupBy(x => x.ProductId)
            .ToDictionary(g => g.Key, g => g.OrderByDescending(x => x.Date).First().SupplierName);
        var map = new Dictionary<string, int>();
        foreach (var s in db.Sales.Where(x => x.Date >= from && x.Date <= to))
        {
            foreach (var it in s.Items)
            {
                var sup = lastSupplier.TryGetValue(it.ProductId, out var v) ? v : "-";
                map[sup] = map.TryGetValue(sup, out var cur) ? cur + it.Total : it.Total;
            }
        }
        return map.Select(kv => (kv.Key, kv.Value)).OrderByDescending(x => x.Item2).ToList();
    }

    public (int sales, int expenses) ProfitLossSummary(DateTime from, DateTime to)
    {
        var db = Load();
        var sales = db.Sales.Where(s => s.Date >= from && s.Date <= to).Sum(s => s.Total);
        var expenses = db.Expenses.Where(e => e.Date >= from && e.Date <= to).Sum(e => e.Amount);
        return (sales, expenses);
    }
    // Suppliers & Customers
    public List<Supplier> Suppliers() => Load().Suppliers.OrderBy(s => s.Name).ToList();
    public List<Customer> Customers() => Load().Customers.OrderBy(c => c.Name).ToList();
    public void UpsertSupplier(Supplier s)
    {
        var db = Load();
        var i = db.Suppliers.FindIndex(x => x.Id == s.Id);
        if (i >= 0) db.Suppliers[i] = s; else db.Suppliers.Add(s);
        Save(db);
    }

    public void DeleteSupplier(string id)
    {
        var db = Load();
        db.Suppliers.RemoveAll(s => s.Id == id);
        Save(db);
    }
    public void UpsertItemType(ItemType t)
    {
        var db = Load();
        var i = db.ItemTypes.FindIndex(x => x.Id == t.Id);
        if (i >= 0) db.ItemTypes[i] = t; else db.ItemTypes.Add(t);
        Save(db);
    }

    public void DeleteItemType(string id)
    {
        var db = Load();
        db.ItemTypes.RemoveAll(t => t.Id == id);
        Save(db);
    }
    public void UpsertCustomer(Customer c)
    {
        var db = Load();
        var i = db.Customers.FindIndex(x => x.Id == c.Id);
        if (i >= 0) db.Customers[i] = c; else db.Customers.Add(c);
        Save(db);
    }

    public void DeleteCustomer(string id)
    {
        var db = Load();
        db.Customers.RemoveAll(c => c.Id == id);
        Save(db);
    }

    public List<Salesman> Salesmen() => Load().Salesmen.OrderBy(s => s.Name).ToList();

    public void UpsertSalesman(Salesman s)
    {
        var db = Load();
        var i = db.Salesmen.FindIndex(x => x.Id == s.Id);
        if (i >= 0) db.Salesmen[i] = s; else db.Salesmen.Add(s);
        Save(db);
    }

    public void DeleteSalesman(string id)
    {
        var db = Load();
        db.Salesmen.RemoveAll(s => s.Id == id);
        Save(db);
    }

    public List<BankAccount> BankAccounts() => Load().BankAccounts.OrderBy(b => b.Bank).ToList();

    public void UpsertBankAccount(BankAccount ba)
    {
        var db = Load();
        var i = db.BankAccounts.FindIndex(x => x.Id == ba.Id);
        if (i >= 0) db.BankAccounts[i] = ba; else db.BankAccounts.Add(ba);
        Save(db);
    }

    public void DeleteBankAccount(string id)
    {
        var db = Load();
        db.BankAccounts.RemoveAll(b => b.Id == id);
        Save(db);
    }

    public List<EdcMachine> EdcMachines() => Load().EdcMachines.OrderBy(em => em.Name).ToList();

    public void UpsertEdcMachine(EdcMachine em)
    {
        var db = Load();
        var i = db.EdcMachines.FindIndex(x => x.Id == em.Id);
        if (i >= 0) db.EdcMachines[i] = em; else db.EdcMachines.Add(em);
        Save(db);
    }

    public void DeleteEdcMachine(string id)
    {
        var db = Load();
        db.EdcMachines.RemoveAll(em => em.Id == id);
        Save(db);
    }

    // Purchases
    public string CreatePurchase(string warehouseId, string supplierName, List<PurchaseItem> items, int discount = 0, int tax = 0, int payNow = 0)
    {
        var db = Load();
        // Find or create supplier by name (simple)
        var supplier = db.Suppliers.FirstOrDefault(s => s.Name.Equals(supplierName, StringComparison.OrdinalIgnoreCase));
        if (supplier is null)
        {
            supplier = new Supplier(Guid.NewGuid().ToString("N"), supplierName);
            db.Suppliers.Add(supplier);
        }
        var subtotal = items.Sum(i => i.Total);
        var total = subtotal - discount + tax;
        var today = DateTime.Now;
        var prefix = $"PI-{today:yyyyMMdd}-";
        var last = db.Purchases.Where(p => p.Number.StartsWith(prefix)).OrderByDescending(p => p.Number).FirstOrDefault();
        var seq = 1;
        if (last != null)
        {
            var tail = last.Number.Substring(prefix.Length);
            if (int.TryParse(tail, out var n)) seq = n + 1;
        }
        var number = $"{prefix}{seq:0000}";
        var id = Guid.NewGuid().ToString("N");
        var purchase = new Purchase(id, number, today, warehouseId, supplier.Id, supplier.Name, items, subtotal, discount, tax, total, payNow >= total ? "PAID" : (payNow > 0 ? "PARTIAL" : "UNPAID"));
        db.Purchases.Add(purchase);
        foreach (var it in items)
        {
            var current = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == warehouseId)?.Qty ?? 0;
            var newQty = current + it.Qty;
            var existing = db.Stocks.FirstOrDefault(x => x.ProductId == it.ProductId && x.WarehouseId == warehouseId);
            if (existing is null) db.Stocks.Add(new Stock(it.ProductId, warehouseId, newQty));
            else
            {
                db.Stocks.Remove(existing);
                db.Stocks.Add(existing with { Qty = newQty });
            }
            db.StockMoves.Add(new StockMove(DateTimeOffset.UtcNow.ToUnixTimeMilliseconds(), it.ProductId, warehouseId, it.Qty, "PURCHASE", id, DateTime.UtcNow));
        }
        if (payNow > 0)
        {
            db.Payments.Add(new Payment(Guid.NewGuid().ToString("N"), "PURCHASE", id, payNow, today));
        }
        var balance = total - payNow;
        if (balance > 0)
        {
            db.Payables.Add(new Payable(Guid.NewGuid().ToString("N"), id, total, payNow, balance));
        }
        Save(db);
        return number;
    }

    public List<Payable> Payables() => Load().Payables.Where(p => p.Balance > 0).ToList();
    public void PayPurchase(string purchaseId, int amount)
    {
        var db = Load();
        var payable = db.Payables.FirstOrDefault(p => p.PurchaseId == purchaseId);
        if (payable is null) return;
        var paid = payable.Paid + amount;
        var bal = Math.Max(0, payable.Total - paid);
        db.Payments.Add(new Payment(Guid.NewGuid().ToString("N"), "PURCHASE", purchaseId, amount, DateTime.Now));
        db.Payables.Remove(payable);
        db.Payables.Add(payable with { Paid = paid, Balance = bal });
        var purchase = db.Purchases.FirstOrDefault(x => x.Id == purchaseId);
        if (purchase != null)
        {
            var status = bal == 0 ? "PAID" : "PARTIAL";
            db.Purchases.Remove(purchase);
            db.Purchases.Add(purchase with { PaymentStatus = status });
        }
        Save(db);
    }

    public string GenerateProductBarcode(string supplierId, string itemTypeId)
    {
        var db = Load();
        var sup = db.Suppliers.FirstOrDefault(s => s.Id == supplierId);
        var typ = db.ItemTypes.FirstOrDefault(t => t.Id == itemTypeId);
        var supCode = (sup?.Code ?? "01").PadLeft(2, '0');
        var typeCode = (typ?.Code ?? "01").PadLeft(2, '0');
        // Find next sequence for given (supplier,type)
        int maxSeq = 0;
        foreach (var pr in db.Products.Where(x => x.SupplierId == supplierId && x.ItemTypeId == itemTypeId))
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
}
