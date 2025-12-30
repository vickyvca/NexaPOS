using System.Collections.ObjectModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class PurchaseReturnView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<PurchaseItem> _originalItems = new();
    private readonly ObservableCollection<Row> _returnItems = new();

    public PurchaseReturnView()
    {
        InitializeComponent();
        OriginalGrid.ItemsSource = _originalItems;
        ReturnGrid.ItemsSource = _returnItems;
    }

    private void Find_OnClick(object sender, RoutedEventArgs e)
    {
        _originalItems.Clear();
        var purchase = _store.LoadPurchases().FirstOrDefault(p => p.Number == TxtNumber.Text.Trim());
        if (purchase == null)
        {
            MessageBox.Show("Nota tidak ditemukan.");
            return;
        }
        foreach (var item in purchase.Items)
        {
            _originalItems.Add(item);
        }
    }

    private void AddItem_OnClick(object sender, RoutedEventArgs e)
    {
        var picker = new ProductPickerWindow();
        if (picker.ShowDialog() == true && picker.Picked is Product p)
        {
            var price = 0; // harga beli tidak selalu tersimpan; isi manual jika perlu
            _returnItems.Add(new Row(p.Id, p.Name, 1, price));
        }
    }

    private void Remove_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button b && b.DataContext is Row r) _returnItems.Remove(r);
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        var number = TxtNumber.Text.Trim();
        if (string.IsNullOrWhiteSpace(number) || _returnItems.Count == 0)
        {
            MessageBox.Show("Isi no pembelian dan item retur");
            return;
        }
        var items = _returnItems.Select(r => new PurchaseItem(r.ProductId, r.Name, r.Qty, r.Price)).ToList();
        if (_store.ReturnPurchaseItems(number, items))
        {
            MessageBox.Show("Retur pembelian tersimpan");
            _originalItems.Clear();
            _returnItems.Clear();
            TxtNumber.Text = string.Empty;
        }
        else
        {
            MessageBox.Show("No pembelian tidak ditemukan");
        }
    }

    public record Row(string ProductId, string Name, int Qty, int Price);
}
