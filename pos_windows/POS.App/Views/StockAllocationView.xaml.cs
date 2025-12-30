using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class StockAllocationView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private Product? _selectedProduct;

    public StockAllocationView()
    {
        InitializeComponent();
        CmbFromWarehouse.ItemsSource = _store.Warehouses();
        CmbFromWarehouse.DisplayMemberPath = nameof(Warehouse.Name);
        CmbFromWarehouse.SelectedValuePath = nameof(Warehouse.Id);

        ToWarehousesList.ItemsSource = _store.Warehouses().Select(w => new SelectableWarehouse(w)).ToList();
    }

    private void SelectProduct_OnClick(object sender, RoutedEventArgs e)
    {
        var picker = new ProductPickerWindow();
        if (picker.ShowDialog() == true && picker.Picked is Product p)
        {
            _selectedProduct = p;
            TxtProduct.Text = p.Name;
        }
    }

    private void Allocate_OnClick(object sender, RoutedEventArgs e)
    {
        if (_selectedProduct == null)
        {
            MessageBox.Show("Pilih produk terlebih dahulu.");
            return;
        }

        if (CmbFromWarehouse.SelectedValue is not string fromWarehouseId)
        {
            MessageBox.Show("Pilih gudang asal.");
            return;
        }

        if (!int.TryParse(TxtQty.Text, out var qty) || qty <= 0)
        {
            MessageBox.Show("Masukkan jumlah yang valid.");
            return;
        }

        var selectedToWarehouses = ((List<SelectableWarehouse>)ToWarehousesList.ItemsSource)
            .Where(w => w.IsSelected)
            .Select(w => w.Warehouse.Id)
            .ToList();

        if (selectedToWarehouses.Count == 0)
        {
            MessageBox.Show("Pilih minimal satu gudang tujuan.");
            return;
        }

        var qtyPerWarehouse = qty / selectedToWarehouses.Count;
        var remainingQty = qty % selectedToWarehouses.Count;

        foreach (var toWarehouseId in selectedToWarehouses)
        {
            var qtyToTransfer = qtyPerWarehouse;
            if (remainingQty > 0)
            {
                qtyToTransfer++;
                remainingQty--;
            }

            if (qtyToTransfer > 0)
            {
                _store.TransferStock(_selectedProduct.Id, fromWarehouseId, toWarehouseId, qtyToTransfer);
            }
        }

        MessageBox.Show("Alokasi stok berhasil.");
        _selectedProduct = null;
        TxtProduct.Text = string.Empty;
        TxtQty.Text = string.Empty;
        foreach (var item in (List<SelectableWarehouse>)ToWarehousesList.ItemsSource)
        {
            item.IsSelected = false;
        }
    }
}

public class SelectableWarehouse : ViewModelBase
{
    private bool _isSelected;
    public Warehouse Warehouse { get; }

    public SelectableWarehouse(Warehouse warehouse)
    {
        Warehouse = warehouse;
    }

    public bool IsSelected
    {
        get => _isSelected;
        set => SetProperty(ref _isSelected, value);
    }

    public string Name => Warehouse.Name;
}
