using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Storage;
using POS.Core;

namespace POS.App.Views;

public partial class StockTransferView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Row> _rows = new();
    public StockTransferView()
    {
        InitializeComponent();
        CmbFrom.ItemsSource = _store.Warehouses();
        CmbFrom.DisplayMemberPath = "Name"; CmbFrom.SelectedValuePath = "Id";
        CmbTo.ItemsSource = _store.Warehouses();
        CmbTo.DisplayMemberPath = "Name"; CmbTo.SelectedValuePath = "Id";
        if (CmbFrom.Items.Count > 0) CmbFrom.SelectedIndex = 0;
        if (CmbTo.Items.Count > 0) CmbTo.SelectedIndex = 0;
        Grid.ItemsSource = _rows;
    }
    private void AddItem_OnClick(object sender, RoutedEventArgs e)
    {
        var picker = new ProductPickerWindow();
        if (picker.ShowDialog() == true && picker.Picked is POS.Core.Product p)
        {
            _rows.Add(new Row(p.Id, p.Name, 1));
        }
    }
    private void Remove_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button btn && btn.DataContext is Row r) _rows.Remove(r);
    }
    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        if (CmbFrom.SelectedValue is not string fromId || CmbTo.SelectedValue is not string toId || fromId == toId)
        { MessageBox.Show("Pilih gudang asal dan tujuan berbeda"); return; }
        foreach (var r in _rows)
        {
            _store.TransferStock(r.ProductId, fromId, toId, r.Qty);
        }
        MessageBox.Show("Mutasi disimpan");
        _rows.Clear();
    }

    public record Row(string ProductId, string Name, int Qty);
}
