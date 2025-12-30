using System;
using System.Windows.Controls;
using POS.Storage;

namespace POS.App.Views;

public partial class ReportProfitLossView : Page
{
    private readonly IStorage _store = StorageService.Current;

    public ReportProfitLossView()
    {
        InitializeComponent();
        DtFrom.SelectedDate = DateTime.Today.AddDays(-30);
        DtTo.SelectedDate = DateTime.Today;
        Apply_OnClick(this, null);
    }

    private void Apply_OnClick(object sender, System.Windows.RoutedEventArgs e)
    {
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        var (sales, expenses) = _store.ProfitLossSummary(from, to);
        var profitLoss = sales - expenses;

        TxtSales.Text = sales.ToString("N0");
        TxtExpenses.Text = expenses.ToString("N0");
        TxtProfitLoss.Text = profitLoss.ToString("N0");
    }
}
