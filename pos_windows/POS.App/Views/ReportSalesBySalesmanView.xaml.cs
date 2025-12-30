using System;
using System.Collections.ObjectModel;
using System.Windows.Controls;
using POS.Storage;

namespace POS.App.Views;

public partial class ReportSalesBySalesmanView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Row> _rows = new();

    public ReportSalesBySalesmanView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        DtFrom.SelectedDate = DateTime.Today.AddDays(-7);
        DtTo.SelectedDate = DateTime.Today;
        Apply_OnClick(this, null);
    }

    private void Apply_OnClick(object sender, System.Windows.RoutedEventArgs e)
    {
        _rows.Clear();
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        foreach (var (name, total) in _store.SalesBySalesman(from, to)) _rows.Add(new Row(name, total));
    }

    public record Row(string Name, int Total);
}
