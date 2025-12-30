using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;
using POS.App;
using POS.Printing;

namespace POS.App.Views;

public partial class PurchaseEditorWindow : Window
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<PurchaseItemModel> _items = new();
    public PurchaseEditorWindow()
    {
        InitializeComponent();
        CmbWarehouse.ItemsSource = _store.Warehouses();
        CmbWarehouse.DisplayMemberPath = nameof(Warehouse.Name);
        CmbWarehouse.SelectedValuePath = nameof(Warehouse.Id);
        if (CmbWarehouse.Items.Count > 0) CmbWarehouse.SelectedIndex = 0;
        GridItems.ItemsSource = _items;
        _items.CollectionChanged += (_, __) => RefreshIndicesAndTotals();
    }

    private void RefreshIndicesAndTotals()
    {
        for (int i = 0; i < _items.Count; i++) _items[i].Index = i + 1;
        var subtotal = _items.Sum(i => i.Total);
        var tax = ChkPpn.IsChecked == true ? (int)System.Math.Round(subtotal * 0.11) : 0;
        TxtTotals.Text = $"Subtotal: {subtotal} | PPN: {tax} | Total: {subtotal + tax}";
    }

    private void AddItem_OnClick(object sender, RoutedEventArgs e)
    {
        var picker = new ProductPickerWindow();
        if (picker.ShowDialog() == true && picker.Picked is Product p)
        {
            var m = new PurchaseItemModel { ProductId = p.Id, Name = p.Name, Qty = 1, Price = 0, PriceH1 = p.PriceH1 ?? 0, PriceH2 = p.PriceH2 ?? 0, PriceGrosir = p.PriceGrosir ?? 0 };
            m.PropertyChanged += (_, __) => RefreshIndicesAndTotals();
            _items.Add(m);
        }
    }

    private void RemoveItem_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button btn && btn.DataContext is PurchaseItemModel row)
        {
            _items.Remove(row);
            RefreshIndicesAndTotals();
        }
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        if (CmbWarehouse.SelectedValue is not string wid) { MessageBox.Show("Pilih gudang"); return; }
        if (string.IsNullOrWhiteSpace(TxtSupplier.Text)) { MessageBox.Show("Isi nama supplier"); return; }
        if (_items.Count == 0) { MessageBox.Show("Tambah item"); return; }
        var items = _items.Select(i => new PurchaseItem(i.ProductId, i.Name, i.Qty, i.Price)).ToList();
        var subtotal = items.Sum(i => i.Total);
        var tax = ChkPpn.IsChecked == true ? (int)System.Math.Round(subtotal * 0.11) : 0;
        var payType = ((ComboBoxItem)CmbPayType.SelectedItem!)?.Content?.ToString() ?? "Tunai";
        var payNow = payType == "Tunai" ? (int.TryParse(TxtPayNow.Text.Trim(), out var v) ? v : subtotal + tax) : 0;

        // Upsert supplier with code
        var code = string.IsNullOrWhiteSpace(TxtSupplierCode.Text) ? null : TxtSupplierCode.Text.Trim();
        var sup = new Supplier(Guid.NewGuid().ToString("N"), TxtSupplier.Text.Trim(), null, code);
        _store.UpsertSupplier(sup);

        // Update product selling prices and ensure barcode
        foreach (var it in _items)
        {
            var prod = _store.Products(it.Name).FirstOrDefault(p => p.Id == it.ProductId);
            if (prod != null)
            {
                prod = prod with { PriceH1 = it.PriceH1, PriceH2 = it.PriceH2, PriceGrosir = it.PriceGrosir };
                _store.UpsertProduct(prod);
            }
        }

        var number = _store.CreatePurchase(wid, TxtSupplier.Text.Trim(), items, 0, tax, payNow);

        // Ask to print barcode labels
        if (MessageBox.Show("Cetak barcode sekarang?", "Barcode", MessageBoxButton.YesNo) == MessageBoxResult.Yes)
        {
            var labels = new List<(string code, string name, int qty, int price)>();
            foreach (var it in _items)
            {
                var prod = _store.Products(it.Name).FirstOrDefault(p => p.Id == it.ProductId);
                if (prod != null && !string.IsNullOrWhiteSpace(prod.Barcode))
                {
                    labels.Add((prod.Barcode!, prod.Name, it.Qty, it.PriceH1));
                }
            }
            try
            {
                var printer = _store.GetSetting("printer.usb.name") ?? new System.Drawing.Printing.PrinterSettings().PrinterName;
                var is80 = (_store.GetSetting("printer.paper") ?? "80mm") == "80mm";
                var bytes = BarcodeLabelBuilder.Build(labels, is80);
                RawPrinter.Print(printer, bytes);
            }
            catch (System.Exception ex)
            {
                MessageBox.Show($"Cetak barcode gagal: {ex.Message}");
            }
        }

        MessageBox.Show($"Disimpan: {number}");
        DialogResult = true;
    }

    private void NewProduct_OnClick(object sender, RoutedEventArgs e)
    {
        var dlg = new QuickProductWindow();
        if (dlg.ShowDialog() == true && dlg.Created != null)
        {
            // add to grid
            var p = dlg.Created;
            var m = new PurchaseItemModel { ProductId = p.Id, Name = p.Name, Qty = 1, Price = 0, PriceH1 = p.PriceH1 ?? 0, PriceH2 = p.PriceH2 ?? 0, PriceGrosir = p.PriceGrosir ?? 0 };
            m.PropertyChanged += (_, __) => RefreshIndicesAndTotals();
            _items.Add(m);
        }
    }
}

public class PurchaseItemModel : INotifyPropertyChanged
{
    public string ProductId { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    private int _qty;
    public int Qty { get => _qty; set { if (value < 1) value = 1; _qty = value; OnPropertyChanged(nameof(Qty)); OnPropertyChanged(nameof(Total)); } }
    private int _price;
    public int Price { get => _price; set { if (value < 0) value = 0; _price = value; OnPropertyChanged(nameof(Price)); OnPropertyChanged(nameof(Total)); } }
    public int PriceH1 { get; set; }
    public int PriceH2 { get; set; }
    public int PriceGrosir { get; set; }
    public int Total => Qty * Price;
    private int _index;
    public int Index { get => _index; set { _index = value; OnPropertyChanged(nameof(Index)); } }
    public event PropertyChangedEventHandler? PropertyChanged;
    private void OnPropertyChanged(string n) => PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(n));
}
