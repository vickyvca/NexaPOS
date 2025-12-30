using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using Microsoft.Win32;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ToolsView : Page
{
    public ToolsView()
    {
        InitializeComponent();
        LoadStoreSettings();
    }

    private void LoadStoreSettings()
    {
        try
        {
            TxtStoreName.Text = StorageService.Current.GetSetting("store.name") ?? string.Empty;
            TxtLogoPath.Text = StorageService.Current.GetSetting("store.logo") ?? string.Empty;
        }
        catch { }
    }

    private void UseJson_OnClick(object sender, RoutedEventArgs e)
    {
        StorageService.Use(new JsonStorage());
        LblStatus.Text = "Switched to JSON storage (restart may be required in some pages).";
        (Application.Current.Windows.OfType<POS.App.MainWindow>().FirstOrDefault())?.RefreshHeader();
    }
    private void UseEf_OnClick(object sender, RoutedEventArgs e)
    {
        StorageService.Use(new EfStorage());
        LblStatus.Text = "Switched to SQLite (EF) storage.";
        (Application.Current.Windows.OfType<POS.App.MainWindow>().FirstOrDefault())?.RefreshHeader();
    }

    private async void Import_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            LblStatus.Text = "Importing from JSON to SQLite...";
            await Task.Run(() => ImportJsonToEf());
            LblStatus.Text = "Import finished.";
        }
        catch (Exception ex)
        {
            LblStatus.Text = "Import failed: " + ex.Message;
        }
    }

    private void ImportJsonToEf()
    {
        var json = new JsonStorage();
        var ef = new EfStorage();
        // Master
        foreach (var t in json.ItemTypes()) ef.UpsertItemType(t);
        foreach (var s in json.Suppliers()) ef.UpsertSupplier(s);
        foreach (var p in json.Products()) ef.UpsertProduct(p);
        // Sales
        foreach (var s in json.LoadSales())
        {
            ef.CreateSale(s.WarehouseId, s.Items, s.Discount, s.Tax, s.CustomerId, s.CustomerName, s.Salesman, s.PaymentMethod);
        }
        // Purchases
        foreach (var p in json.LoadPurchases())
        {
            ef.CreatePurchase(p.WarehouseId, p.SupplierName, p.Items, p.Discount, p.Tax, 0);
        }
    }

    private async void Seed_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            LblStatus.Text = "Seeding sample data...";
            await Task.Run(() => SeedSampleData());
            LblStatus.Text = "Seeding done.";
        }
        catch (Exception ex)
        {
            LblStatus.Text = "Seeding failed: " + ex.Message;
        }
    }

    private void SeedSampleData()
    {
        var store = StorageService.Current;
        var rnd = new Random(1234);
        // ensure item types & suppliers
        var types = store.ItemTypes();
        if (types.Count < 10)
        {
            for (int i = 1; i <= 10; i++)
            {
                var t = new ItemType(Guid.NewGuid().ToString("N"), i.ToString().PadLeft(2, '0'), $"Jenis {i}");
                store.UpsertItemType(t);
            }
            types = store.ItemTypes();
        }
        var sups = store.Suppliers();
        if (sups.Count < 10)
        {
            for (int i = 1; i <= 10; i++)
            {
                var s = new Supplier(Guid.NewGuid().ToString("N"), $"Supplier {i}", null, i.ToString().PadLeft(2, '0'));
                store.UpsertSupplier(s);
            }
            sups = store.Suppliers();
        }
        // products 300
        var adjectives = new[] { "Urban", "Classic", "Crystal", "Indigo", "Radiant", "Noir", "Starlight", "Leather", "FrostWave", "Aurora" };
        var nouns = new[] { "Tee", "Polo", "Skirt", "Denim", "Jacket", "Chinos", "Dress", "Jumper", "Hoodie", "Sneakers" };
        var products = store.Products();
        int toCreate = Math.Max(0, 300 - products.Count);
        for (int i = 0; i < toCreate; i++)
        {
            var name = $"{adjectives[rnd.Next(adjectives.Length)]} {nouns[rnd.Next(nouns.Length)]} {rnd.Next(1000, 9999)}";
            var sup = sups[rnd.Next(sups.Count)];
            var typ = types[rnd.Next(types.Count)];
            var barcode = store.GenerateProductBarcode(sup.Id, typ.Id);
            var h1 = rnd.Next(15000, 150000);
            var h2 = h1 - rnd.Next(1000, 5000);
            var g = h1 - rnd.Next(2000, 8000);
            var p = new Product(Guid.NewGuid().ToString("N"), name, barcode, null, null, true, h1, h2, g, "ART-" + rnd.Next(1000, 9999), sup.Id, typ.Id);
            store.UpsertProduct(p);
        }
        products = store.Products();
        var warehouses = store.Warehouses();
        var wid = warehouses.First().Id;
        // seed purchases 200
        for (int i = 0; i < 200; i++)
        {
            int itemCount = rnd.Next(1, 6);
            var items = new List<PurchaseItem>();
            for (int j = 0; j < itemCount; j++)
            {
                var pr = products[rnd.Next(products.Count)];
                var qty = rnd.Next(1, 20);
                var price = rnd.Next(8000, 90000);
                items.Add(new PurchaseItem(pr.Id, pr.Name, qty, price));
            }
            var sup = sups[rnd.Next(sups.Count)];
            store.CreatePurchase(wid, sup.Name, items, 0, 0, 0);
        }
        // seed sales 200
        for (int i = 0; i < 200; i++)
        {
            int itemCount = rnd.Next(1, 5);
            var items = new List<SaleItem>();
            for (int j = 0; j < itemCount; j++)
            {
                var pr = products[rnd.Next(products.Count)];
                var qty = rnd.Next(1, 5);
                var price = (pr.PriceH1 ?? 20000);
                items.Add(new SaleItem(pr.Id, pr.Name, qty, price));
            }
            store.CreateSale(wid, items, 0, 0, null, "Umum", null, "Tunai");
        }
    }

    private void SaveStore_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            var name = TxtStoreName.Text?.Trim() ?? string.Empty;
            if (!string.IsNullOrWhiteSpace(name)) StorageService.Current.SetSetting("store.name", name);
            (Application.Current.Windows.OfType<POS.App.MainWindow>().FirstOrDefault())?.RefreshHeader();
            LblStatus.Text = "Nama toko disimpan.";
        }
        catch (Exception ex) { LblStatus.Text = "Gagal simpan: " + ex.Message; }
    }

    private void PickLogo_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            var dlg = new OpenFileDialog { Filter = "Image Files|*.png;*.jpg;*.jpeg" };
            if (dlg.ShowDialog() == true)
            {
                TxtLogoPath.Text = dlg.FileName;
                StorageService.Current.SetSetting("store.logo", dlg.FileName);
                (Application.Current.Windows.OfType<POS.App.MainWindow>().FirstOrDefault())?.RefreshHeader();
                LblStatus.Text = "Logo disimpan.";
            }
        }
        catch (Exception ex) { LblStatus.Text = "Gagal pilih logo: " + ex.Message; }
    }
}
