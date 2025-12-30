using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using POS.Storage;

namespace POS.App.Views;

public partial class StockOpnameView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Row> _rows = new();

    public StockOpnameView()
    {
        InitializeComponent();
        CmbWarehouse.ItemsSource = _store.Warehouses();
        CmbWarehouse.DisplayMemberPath = "Name";
        CmbWarehouse.SelectedValuePath = "Id";
        if (CmbWarehouse.Items.Count > 0) CmbWarehouse.SelectedIndex = 0;
        Grid.ItemsSource = _rows;
    }

    private void Load_OnClick(object sender, RoutedEventArgs e)
    {
        _rows.Clear();
        if (CmbWarehouse.SelectedValue is not string wid) return;
        var products = _store.Products(TxtSearch.Text.Trim());
        foreach (var p in products)
        {
            var qty = _store.GetStock(p.Id, wid);
            _rows.Add(new Row(p.Id, p.Name, qty, qty));
        }
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        if (CmbWarehouse.SelectedValue is not string wid) return;
        var payload = _rows.Select(r => (r.ProductId, r.QtyReal)).ToList();
        _store.OpnameSetQuantities(wid, payload);
        MessageBox.Show("Opname disimpan");
    }

    public class Row : INotifyPropertyChanged
    {
        public string ProductId { get; }
        public string Name { get; }
        public int QtySystem { get; }
        private int _qtyReal;

        public Row(string productId, string name, int qtySystem, int qtyReal)
        {
            ProductId = productId;
            Name = name;
            QtySystem = qtySystem;
            _qtyReal = qtyReal;
        }

        public int QtyReal
        {
            get => _qtyReal;
            set
            {
                if (_qtyReal == value) return;
                _qtyReal = value;
                OnPropertyChanged(nameof(QtyReal));
                OnPropertyChanged(nameof(Difference));
            }
        }

        public int Difference => QtyReal - QtySystem;

        public event PropertyChangedEventHandler? PropertyChanged;

        protected virtual void OnPropertyChanged(string propertyName)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}
