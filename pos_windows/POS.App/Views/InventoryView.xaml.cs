using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using Microsoft.VisualBasic;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class InventoryView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<StockRow> _rows = new();

    public InventoryView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        CmbWarehouse.ItemsSource = _store.Warehouses();
        CmbWarehouse.DisplayMemberPath = nameof(Warehouse.Name);
        CmbWarehouse.SelectedValuePath = nameof(Warehouse.Id);
        if (CmbWarehouse.Items.Count > 0) CmbWarehouse.SelectedIndex = 0;
        Reload();
        Grid.MouseDoubleClick += (_, __) => AdjustSelected();
    }

    private void Reload()
    {
        _rows.Clear();
        if (CmbWarehouse.SelectedValue is not string wid) return;
        var products = _store.Products(TxtSearch.Text.Trim());
        foreach (var p in products)
        {
            _rows.Add(new StockRow(p.Id, p.Name, p.Barcode, p.Sku, _store.GetStock(p.Id, wid)));
        }
    }

    private void OnWarehouseChanged(object sender, SelectionChangedEventArgs e) => Reload();

    private void Search_OnClick(object sender, RoutedEventArgs e) => Reload();

    private void TxtSearch_OnKeyDown(object sender, KeyEventArgs e)
    {
        if (e.Key == Key.Enter) Reload();
    }

    private void Adjust_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not StockRow row) return;
        if (CmbWarehouse.SelectedValue is not string wid) return;
        var input = Interaction.InputBox("Qty baru:", "Set Qty", row.Qty.ToString());
        if (int.TryParse(input, out var target))
        {
            _store.SetStock(row.ProductId, wid, target, "ADJUST");
            Reload();
        }
    }

    private void AdjustSelected()
    {
        if (Grid.SelectedItem is not StockRow row) return;
        if (CmbWarehouse.SelectedValue is not string wid) return;
        var input = Interaction.InputBox("Qty baru:", "Set Qty", row.Qty.ToString());
        if (int.TryParse(input, out var target))
        {
            _store.SetStock(row.ProductId, wid, target, "ADJUST");
            Reload();
        }
    }
}

public record StockRow(string ProductId, string Name, string? Barcode, string? Sku, int Qty);
