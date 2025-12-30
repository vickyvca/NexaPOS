using System;
using System.Windows.Controls;
using POS.Storage;

namespace POS.App.Views;

public partial class ReportSalesTotalView : Page
{
    private readonly IStorage _store = StorageService.Current;

    public ReportSalesTotalView()
    {
        InitializeComponent();
        DtFrom.SelectedDate = DateTime.Today.AddDays(-7);
        DtTo.SelectedDate = DateTime.Today;
        Apply_OnClick(this, null);
    }

    private void Apply_OnClick(object sender, System.Windows.RoutedEventArgs e)
    {
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        var s = _store.SalesSummary(from, to);
        TxtSubtotal.Text = s.subtotal.ToString("N0");
        TxtTax.Text = s.tax.ToString("N0");
        TxtTotal.Text = s.total.ToString("N0");
    }
}
