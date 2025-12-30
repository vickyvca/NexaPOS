using System.Collections.ObjectModel;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using Microsoft.VisualBasic;
using POS.Storage;
using POS.App;

namespace POS.App.Views;

public partial class PayablesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Row> _rows = new();
    public PayablesView()
    {
        InitializeComponent();
        List.ItemsSource = _rows;
        LoadRows();
    }

    private void LoadRows()
    {
        _rows.Clear();
        var map = _store.LoadPurchases().ToDictionary(p => p.Id, p => p);
        foreach (var p in _store.Payables())
        {
            if (!map.TryGetValue(p.PurchaseId, out var inv)) continue;
            _rows.Add(new Row(inv.Id, inv.Number, inv.SupplierName, p.Total, p.Paid, p.Balance));
        }
    }

    private void Refresh_OnClick(object sender, RoutedEventArgs e) => LoadRows();

    private void Pay_OnDoubleClick(object sender, RoutedEventArgs e)
    {
        if (List.SelectedItem is not Row r) return;
        var input = Interaction.InputBox($"Bayar hutang untuk {r.PurchaseNumber} (sisa {r.Balance}):", "Bayar Hutang", r.Balance.ToString());
        if (int.TryParse(input, out var amount) && amount > 0)
        {
            _store.PayPurchase(r.PurchaseId, amount);
            LoadRows();
        }
    }

    public record Row(string PurchaseId, string PurchaseNumber, string SupplierName, int Total, int Paid, int Balance);
}
