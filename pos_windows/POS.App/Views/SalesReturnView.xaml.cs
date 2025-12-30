using System.Collections.ObjectModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class SalesReturnView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<SaleItem> _originalItems = new();
    private readonly ObservableCollection<Row> _replacementItems = new();

    public SalesReturnView()
    {
        InitializeComponent();
        OriginalGrid.ItemsSource = _originalItems;
        ReplacementGrid.ItemsSource = _replacementItems;
    }

    private void Find_OnClick(object sender, RoutedEventArgs e)
    {
        _originalItems.Clear();
        var sale = _store.LoadSales().FirstOrDefault(s => s.Number == TxtNumber.Text.Trim());
        if (sale == null)
        {
            MessageBox.Show("Nota tidak ditemukan.");
            return;
        }
        foreach (var item in sale.Items)
        {
            _originalItems.Add(item);
        }
    }

    private void AddItem_OnClick(object sender, RoutedEventArgs e)
    {
        var picker = new ProductPickerWindow();
        if (picker.ShowDialog() == true && picker.Picked is Product p)
        {
            var price = p.PriceH1 ?? p.PriceH2 ?? p.PriceGrosir ?? 0;
            _replacementItems.Add(new Row(p.Id, p.Name, 1, price));
        }
    }

    private void Remove_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button b && b.DataContext is Row r) _replacementItems.Remove(r);
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        var number = TxtNumber.Text.Trim();
        if (string.IsNullOrWhiteSpace(number) || _replacementItems.Count == 0)
        {
            MessageBox.Show("Isi no nota dan item pengganti");
            return;
        }
        var items = _replacementItems.Select(r => new SaleItem(r.ProductId, r.Name, r.Qty, r.Price)).ToList();
        if (_store.ReplaceSaleItems(number, items))
        {
            MessageBox.Show("Retur tersimpan");
            _originalItems.Clear();
            _replacementItems.Clear();
            TxtNumber.Text = string.Empty;
        }
        else
        {
            MessageBox.Show("Nota tidak ditemukan");
        }
    }

    public record Row(string ProductId, string Name, int Qty, int Price);
}
