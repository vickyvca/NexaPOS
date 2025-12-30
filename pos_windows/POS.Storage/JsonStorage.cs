using System;
using System.Collections.Generic;
using System.Linq;
using POS.Core;
using POS.Data;

namespace POS.Storage;

public class JsonStorage : IStorage
{
    private readonly JsonDatabase _db = new();
    public List<ItemType> ItemTypes() => _db.ItemTypes();
    public void UpsertItemType(ItemType t) => _db.UpsertItemType(t);
    public void DeleteItemType(string id) => _db.DeleteItemType(id);
    public List<Supplier> Suppliers() => _db.Suppliers();
    public void UpsertSupplier(Supplier s) => _db.UpsertSupplier(s);
    public void DeleteSupplier(string id) => _db.DeleteSupplier(id);
    public List<Product> Products(string? query = null) => _db.Products(query);
    public void UpsertProduct(Product p) => _db.UpsertProduct(p);
    public void DeleteProduct(string id) => _db.DeleteProduct(id);
    public string GenerateProductBarcode(string supplierId, string itemTypeId) => _db.GenerateProductBarcode(supplierId, itemTypeId);
    public List<Warehouse> Warehouses() => _db.Warehouses();
    public int GetStock(string productId, string warehouseId) => _db.GetStock(productId, warehouseId);
    public void SetStock(string productId, string warehouseId, int qty, string reason = "ADJUST") => _db.SetStock(productId, warehouseId, qty, reason);
    public void TransferStock(string productId, string fromWarehouseId, string toWarehouseId, int qty) => _db.TransferStock(productId, fromWarehouseId, toWarehouseId, qty);
    public void OpnameSetQuantities(string warehouseId, List<(string productId, int qty)> rows) => _db.OpnameSetQuantities(warehouseId, rows);
    public bool ReplaceSaleItems(string saleNumber, List<SaleItem> newItems) => _db.ReplaceSaleItems(saleNumber, newItems);
    public bool ReturnPurchaseItems(string purchaseNumber, List<PurchaseItem> retItems) => _db.ReturnPurchaseItems(purchaseNumber, retItems);
    public void AddExpense(DateTime date, string account, string description, int amount) => _db.AddExpense(date, account, description, amount);
    public List<Expense> ListExpenses(DateTime? from = null, DateTime? to = null) => _db.ListExpenses(from, to);
    public void DeleteExpense(string id) => _db.DeleteExpense(id);
    public void AddAccountTransfer(DateTime date, string fromAccount, string toAccount, int amount, string? note = null) => _db.AddAccountTransfer(date, fromAccount, toAccount, amount, note);
    public List<AccountTransfer> ListAccountTransfers(DateTime? from = null, DateTime? to = null) => _db.ListAccountTransfers(from, to);
    public void DeleteAccountTransfer(string id) => _db.DeleteAccountTransfer(id);
    public string CreateSale(string warehouseId, List<SaleItem> items, int discount = 0, int tax = 0, string? customerId = null, string? customerName = null, string? salesman = null, string? paymentMethod = null)
        => _db.CreateSale(warehouseId, items, discount, tax, customerId, customerName, salesman, paymentMethod);
    public string CreatePurchase(string warehouseId, string supplierName, List<PurchaseItem> items, int discount = 0, int tax = 0, int payNow = 0)
        => _db.CreatePurchase(warehouseId, supplierName, items, discount, tax, payNow);
    public List<Sale> LoadSales() => _db.LoadSales();
    public void DeleteSale(string saleId) => _db.DeleteSale(saleId);
    public List<Purchase> LoadPurchases() => _db.LoadPurchases();
    public void DeletePurchase(string purchaseId) => _db.DeletePurchase(purchaseId);
    public List<Payable> Payables() => _db.Payables();
    public void PayPurchase(string purchaseId, int amount) => _db.PayPurchase(purchaseId, amount);
    public List<Customer> Customers() => _db.Customers();
    public void UpsertCustomer(Customer c) => _db.UpsertCustomer(c);
    public void DeleteCustomer(string id) => _db.DeleteCustomer(id);
    public List<Salesman> Salesmen() => _db.Salesmen();
    public void UpsertSalesman(Salesman s) => _db.UpsertSalesman(s);
    public void DeleteSalesman(string id) => _db.DeleteSalesman(id);
    public List<BankAccount> BankAccounts() => _db.BankAccounts();
    public void UpsertBankAccount(BankAccount ba) => _db.UpsertBankAccount(ba);
    public void DeleteBankAccount(string id) => _db.DeleteBankAccount(id);
    public List<EdcMachine> EdcMachines() => _db.EdcMachines();
    public void UpsertEdcMachine(EdcMachine em) => _db.UpsertEdcMachine(em);
    public void DeleteEdcMachine(string id) => _db.DeleteEdcMachine(id);
    public string? GetSetting(string key) => _db.GetSetting(key);
    public void SetSetting(string key, string value) => _db.SetSetting(key, value);
    public (int subtotal, int tax, int total) SalesSummary(DateTime from, DateTime to) => _db.SalesSummary(from, to);
    public List<(string Name, int Total)> SalesBySalesman(DateTime from, DateTime to)
        => _db.SalesBySalesman(from, to).Select(x => (Name: x.salesman, Total: x.total)).ToList();
    public List<(string Name, int Total)> SalesBySupplier(DateTime from, DateTime to)
        => _db.SalesBySupplier(from, to).Select(x => (Name: x.supplier, Total: x.total)).ToList();

    public (int sales, int expenses) ProfitLossSummary(DateTime from, DateTime to) => _db.ProfitLossSummary(from, to);
}
