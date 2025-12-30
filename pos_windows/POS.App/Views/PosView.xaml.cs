using System.Collections.ObjectModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;
using POS.App;
using POS.Printing;
using System.Windows.Input;
using System.ComponentModel;

namespace POS.App.Views;

public partial class PosView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<CartItemModel> _cart = new();
    private readonly ObservableCollection<ProductCardModel> _catalog = new();
    private string _payMethod = "Cash";
    private int _page = 1;
    private int _pageSize = 15;
    private int _totalProducts = 0;

    public PosView()
    {
        InitializeComponent();

        try
        {
 ListSummary.ItemsSource = _cart;
     CatalogGrid.ItemsSource = _catalog;
            CmbLevel.SelectedIndex = 0;
            CmbPageSize.SelectedIndex = 0; // 15 default
       
     var warehouses = _store.Warehouses()?.ToList() ?? new List<Warehouse>();
   CmbWarehouse.ItemsSource = warehouses;
   CmbWarehouse.DisplayMemberPath = nameof(Warehouse.Name);
            CmbWarehouse.SelectedValuePath = nameof(Warehouse.Id);
            if (warehouses.Any()) CmbWarehouse.SelectedIndex = 0;
    
            _cart.CollectionChanged += (_, __) => Application.Current.Dispatcher.BeginInvoke(new Action(RefreshIndicesAndTotals));
            ReloadCatalog();
        UpdateTotals();
        }
        catch (Exception ex)
 {
        MessageBox.Show($"Error initializing view: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
     }
    }

    private void TxtScan_OnKeyDown(object sender, KeyEventArgs e)
    {
try
        {
       if (e.Key != Key.Enter) return;
            var q = TxtScan?.Text?.Trim() ?? "";
        if (string.IsNullOrEmpty(q)) return;

     var level = ((ComboBoxItem)CmbLevel.SelectedItem)?.Content?.ToString() ?? "H1";
            var p = _store.Products(q).FirstOrDefault();
  if (p is null)
          {
       MessageBox.Show($"Produk tidak ditemukan: {q}");
         return;
 }
            
     var price = level switch
            {
         "H1" => p.PriceH1 ?? 0,
      "H2" => p.PriceH2 ?? 0,
         _ => p.PriceGrosir ?? 0
            };
     
         var model = new CartItemModel { ProductId = p.Id, Name = p.Name, Qty = 1, Price = price };
            model.PropertyChanged += (_, __) => UpdateTotals();
     _cart.Add(model);
TxtScan.Text = string.Empty;
            UpdateTotals();
        }
        catch (Exception ex)
   {
            MessageBox.Show($"Error scanning product: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void UpdateTotals()
    {
        try
        {
            var subtotal = _cart?.Sum(i => i.Total) ?? 0;
            var tax = ChkPpn?.IsChecked == true ? (int)Math.Round(subtotal * 0.11) : 0;
            var total = subtotal + tax;
            if (TxtTotalBig != null) TxtTotalBig.Text = $"Total: Rp {total:N0}";
            if (TxtSubAndTax != null) TxtSubAndTax.Text = $"Subtotal: Rp {subtotal:N0}    PPN: Rp {tax:N0}";
        }
  catch (Exception ex)
        {
            MessageBox.Show($"Error updating totals: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Pay_OnClick(object sender, RoutedEventArgs e)
    {
     try
     {
            if (CmbWarehouse?.SelectedValue is not string wh || _cart?.Count == 0)
            {
        MessageBox.Show("Please select a warehouse and add items to cart", "Warning", MessageBoxButton.OK, MessageBoxImage.Warning);
          return;
            }

    var items = _cart.Select(i => new SaleItem(i.ProductId, i.Name, i.Qty, i.Price)).ToList();
       var subtotal = items.Sum(i => i.Total);
    var tax = ChkPpn?.IsChecked == true ? (int)Math.Round(subtotal * 0.11) : 0;

            var payWin = new SalePaymentWindow();
     if (payWin.ShowDialog() != true) return;

        var pm = string.IsNullOrWhiteSpace(payWin.PaymentMethod) ? _payMethod : payWin.PaymentMethod;
     var number = _store.CreateSale(wh, items, 0, tax, payWin.CustomerId, payWin.CustomerName, payWin.Salesman, pm);
            
     // Print
            var backend = _store.GetSetting("printer.backend") ?? "USB";
            if (backend == "USB")
            {
      var printer = _store.GetSetting("printer.usb.name") ?? DefaultPrinter();
  var paper = (_store.GetSetting("printer.paper") ?? "80mm").Equals("80mm", StringComparison.OrdinalIgnoreCase);
            var bytes = ReceiptBuilder.BuildSaleReceipt(
             is80mm: paper,
     title: "STRUK PENJUALAN",
           invoiceNumber: number,
          date: DateTime.Now,
           items: items,
       subtotal: subtotal,
            discount: 0,
        tax: tax,
         total: subtotal + tax
  );
      try { RawPrinter.Print(printer, bytes); }
    catch (Exception ex) { MessageBox.Show($"Cetak gagal: {ex.Message}", "Print Error", MessageBoxButton.OK, MessageBoxImage.Warning); }
            }

            MessageBox.Show($"Transaksi tersimpan: {number}", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
    _cart.Clear();
        UpdateTotals();
    }
        catch (Exception ex)
    {
          MessageBox.Show($"Error processing payment: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private static string DefaultPrinter()
    {
        try
        {
   return new System.Drawing.Printing.PrinterSettings().PrinterName;
        }
        catch
        {
            return string.Empty;
        }
    }

private void RefreshIndicesAndTotals()
  {
        try
        {
       for (int i = 0; i < _cart?.Count; i++) _cart[i].Index = i + 1;
            UpdateTotals();
        }
        catch (Exception ex)
  {
         MessageBox.Show($"Error refreshing cart: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
     }
    }

    private void Cart_Remove_Click(object sender, RoutedEventArgs e)
    {
        try
        {
            if (sender is Button btn && btn.DataContext is CartItemModel row)
{
       _cart?.Remove(row);
     RefreshIndicesAndTotals();
}
        }
        catch (Exception ex)
        {
        MessageBox.Show($"Error removing item: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Search_OnClick(object sender, RoutedEventArgs e)
  {
        try
     {
            ReloadCatalog();
     }
catch (Exception ex)
        {
          MessageBox.Show($"Error searching: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Warehouse_Changed(object sender, SelectionChangedEventArgs e) { _page = 1; ReloadCatalog(); }
    private void Level_Changed(object sender, SelectionChangedEventArgs e) { _page = 1; ReloadCatalog(); }

    private void AddToCart_Click(object sender, RoutedEventArgs e)
    {
        try
        {
            if (sender is Button btn && btn.DataContext is ProductCardModel p)
  {
       var model = new CartItemModel { ProductId = p.Id, Name = p.Name, Qty = 1, Price = p.Price };
       model.PropertyChanged += (_, __) => UpdateTotals();
      _cart?.Add(model);
  ListSummary?.Items?.Refresh();
  UpdateTotals();
   }
        }
        catch (Exception ex)
   {
       MessageBox.Show($"Error adding to cart: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void PayMethod_Cash(object sender, RoutedEventArgs e) { _payMethod = "Cash"; TriggerPay(); }
    private void PayMethod_Card(object sender, RoutedEventArgs e) { _payMethod = "Card"; TriggerPay(); }
    private void PayMethod_Wallet(object sender, RoutedEventArgs e) { _payMethod = "Wallet"; TriggerPay(); }
    
    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
   try
        {
     _cart?.Clear();
            UpdateTotals();
        }
 catch (Exception ex)
        {
   MessageBox.Show($"Error canceling: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
      }
    }

    private void ReloadCatalog()
    {
try
 {
      _catalog?.Clear();
            if (CmbWarehouse?.SelectedValue is not string wid) return;
      
  var level = ((ComboBoxItem)CmbLevel.SelectedItem)?.Content?.ToString() ?? "H1";
       var searchText = TxtScan?.Text?.Trim() ?? "";
            
          var all = _store.Products(searchText);
          _totalProducts = all.Count;
          var pages = Math.Max(1, (int)Math.Ceiling((double)_totalProducts / _pageSize));
          if (_page > pages) _page = pages;
          var pageItems = all.Skip((_page - 1) * _pageSize).Take(_pageSize).ToList();
          TxtPageInfo.Text = $"{_page} / {pages} | {_totalProducts} items";

          foreach (var p in pageItems)
   {
      var price = level switch
        {
 "H1" => p.PriceH1 ?? 0,
       "H2" => p.PriceH2 ?? 0,
           _ => p.PriceGrosir ?? 0
          };
  var stock = _store.GetStock(p.Id, wid);
       _catalog.Add(new ProductCardModel
 {
        Id = p.Id,
           Name = p.Name,
                 Price = price,
     PriceText = $"Rp {price:N0}",
       StockText = $"{stock} pcs",
                });
            }
        }
        catch (Exception ex)
        {
 MessageBox.Show($"Error reloading catalog: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
      }
    }

    private void PagePrev_Click(object sender, RoutedEventArgs e)
    {
        if (_page > 1) { _page--; ReloadCatalog(); }
    }
    private void PageNext_Click(object sender, RoutedEventArgs e)
    {
        var pages = Math.Max(1, (int)Math.Ceiling((double)_totalProducts / _pageSize));
        if (_page < pages) { _page++; ReloadCatalog(); }
    }
    private void PageSize_Changed(object sender, SelectionChangedEventArgs e)
    {
        try
        {
            var item = (CmbPageSize.SelectedItem as ComboBoxItem)?.Content?.ToString();
            if (int.TryParse(item, out var ps) && ps > 0)
            {
                _pageSize = ps;
                _page = 1;
                ReloadCatalog();
            }
        }
        catch { }
    }
    private void TriggerPay()
    {
        try { Pay_OnClick(this, new RoutedEventArgs()); } catch { }
    }
}

public class CartItemModel : INotifyPropertyChanged
{
    public string ProductId { get; set; } = string.Empty;
 public string Name { get; set; } = string.Empty;
    private int _qty;
    public int Qty 
    { 
        get => _qty;
        set 
        {
if (value < 1) value = 1;
     if (_qty != value)
      {
    _qty = value;
           OnPropertyChanged(nameof(Qty));
         OnPropertyChanged(nameof(Total));
         }
     }
    }
    
    private int _price;
    public int Price 
 { 
        get => _price;
    set 
        {
            if (value < 0) value = 0;
        if (_price != value)
     {
           _price = value;
      OnPropertyChanged(nameof(Price));
        OnPropertyChanged(nameof(Total));
         }
        }
    }
    
    public int Total => Qty * Price;
    
    private int _index;
    public int Index 
    { 
   get => _index;
        set 
        {
   if (_index != value)
     {
 _index = value;
     OnPropertyChanged(nameof(Index));
            }
        }
    }
    
    public event PropertyChangedEventHandler? PropertyChanged;
    private void OnPropertyChanged(string n) => PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(n));
}

public class ProductCardModel
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public int Price { get; set; }
    public string PriceText { get; set; } = string.Empty;
  public string StockText { get; set; } = string.Empty;
}
