using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ExpensesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Expense> _rows = new();

    public ExpensesView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        DtFrom.SelectedDate = DateTime.Today.AddDays(-30);
        DtTo.SelectedDate = DateTime.Today;
        Load();
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var date = DtDate.SelectedDate ?? DateTime.Now;
        var acc = TxtAccount.Text.Trim();
        var desc = TxtDesc.Text.Trim();
        var amt = int.TryParse(TxtAmount.Text.Trim(), out var v) ? v : 0;
        if (string.IsNullOrWhiteSpace(acc) || amt <= 0)
        {
            MessageBox.Show("Isi akun dan jumlah valid");
            return;
        }
        _store.AddExpense(date, acc, desc, amt);
        Load();
        TxtAccount.Text = string.Empty;
        TxtDesc.Text = string.Empty;
        TxtAmount.Text = string.Empty;
    }

    private void Filter_OnClick(object sender, RoutedEventArgs e) => Load();

    private void Load()
    {
        _rows.Clear();
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        foreach (var x in _store.ListExpenses(from, to)) _rows.Add(x);
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Expense ex) return;
        if (MessageBox.Show($"Hapus pengeluaran {ex.Description}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteExpense(ex.Id);
        Load();
    }
}
