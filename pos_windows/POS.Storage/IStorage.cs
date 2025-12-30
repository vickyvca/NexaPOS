using System;
using System.Collections.Generic;
using POS.Core;

namespace POS.Storage;

public interface IStorage
{
    // Item types (Jenis Barang)
    List<ItemType> ItemTypes();
    void UpsertItemType(ItemType t);
    void DeleteItemType(string id);

    // Suppliers
    List<Supplier> Suppliers();
    void UpsertSupplier(Supplier s);
    void DeleteSupplier(string id);

    // Products
    List<Product> Products(string? query = null);
    void UpsertProduct(Product p);
    void DeleteProduct(string id);
    string GenerateProductBarcode(string supplierId, string itemTypeId);

    // Warehouses
    List<Warehouse> Warehouses();

    // Stock helpers
    int GetStock(string productId, string warehouseId);
    void SetStock(string productId, string warehouseId, int qty, string reason = "ADJUST");
    void TransferStock(string productId, string fromWarehouseId, string toWarehouseId, int qty);
    void OpnameSetQuantities(string warehouseId, List<(string productId, int qty)> rows);

    // Returns
    bool ReplaceSaleItems(string saleNumber, List<SaleItem> newItems);
    bool ReturnPurchaseItems(string purchaseNumber, List<PurchaseItem> retItems);

    // Back office
    void AddExpense(DateTime date, string account, string description, int amount);
    List<Expense> ListExpenses(DateTime? from = null, DateTime? to = null);
    void DeleteExpense(string id);
    void AddAccountTransfer(DateTime date, string fromAccount, string toAccount, int amount, string? note = null);
    List<AccountTransfer> ListAccountTransfers(DateTime? from = null, DateTime? to = null);
    void DeleteAccountTransfer(string id);

    // Sales & Purchases
    string CreateSale(string warehouseId, List<SaleItem> items, int discount = 0, int tax = 0, string? customerId = null, string? customerName = null, string? salesman = null, string? paymentMethod = null);
    string CreatePurchase(string warehouseId, string supplierName, List<PurchaseItem> items, int discount = 0, int tax = 0, int payNow = 0);
    List<Sale> LoadSales();
    void DeleteSale(string saleId);
    List<Purchase> LoadPurchases();
    void DeletePurchase(string purchaseId);
    List<Payable> Payables();
    void PayPurchase(string purchaseId, int amount);

    // Customers
    List<Customer> Customers();
    void UpsertCustomer(Customer c);
    void DeleteCustomer(string id);

    // Salesmen
    List<Salesman> Salesmen();
    void UpsertSalesman(Salesman s);
    void DeleteSalesman(string id);

    // Bank Accounts
    List<BankAccount> BankAccounts();
    void UpsertBankAccount(BankAccount ba);
    void DeleteBankAccount(string id);

    // EDC Machines
    List<EdcMachine> EdcMachines();
    void UpsertEdcMachine(EdcMachine em);
    void DeleteEdcMachine(string id);

    // Settings
    string? GetSetting(string key);
    void SetSetting(string key, string value);

    // Reports
    (int subtotal, int tax, int total) SalesSummary(DateTime from, DateTime to);
    List<(string Name, int Total)> SalesBySalesman(DateTime from, DateTime to);
    List<(string Name, int Total)> SalesBySupplier(DateTime from, DateTime to);
    (int sales, int expenses) ProfitLossSummary(DateTime from, DateTime to);
}