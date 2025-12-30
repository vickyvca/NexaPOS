using System;
using System.Collections.ObjectModel;
using System.Linq;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ReportPurchasesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Purchase> _rows = new();

    public ReportPurchasesView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        DtFrom.SelectedDate = DateTime.Today.AddDays(-30);
        DtTo.SelectedDate = DateTime.Today;
        Apply_OnClick(this, null);
    }

    private void Apply_OnClick(object sender, System.Windows.RoutedEventArgs e)
    {
        _rows.Clear();
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        foreach (var p in _store.LoadPurchases().Where(p => p.Date >= from && p.Date <= to))
        {
            _rows.Add(p);
        }
    }
}
