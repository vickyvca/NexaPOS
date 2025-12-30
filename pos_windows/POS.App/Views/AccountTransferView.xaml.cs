using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class AccountTransferView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<AccountTransfer> _rows = new();

    public AccountTransferView()
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
        var from = TxtFrom.Text.Trim();
        var to = TxtTo.Text.Trim();
        var amt = int.TryParse(TxtAmount.Text.Trim(), out var v) ? v : 0;
        var note = TxtNote.Text.Trim();
        if (string.IsNullOrWhiteSpace(from) || string.IsNullOrWhiteSpace(to) || amt <= 0)
        {
            MessageBox.Show("Isi akun dan jumlah valid");
            return;
        }
        _store.AddAccountTransfer(date, from, to, amt, string.IsNullOrWhiteSpace(note) ? null : note);
        Load();
        TxtFrom.Text = string.Empty;
        TxtTo.Text = string.Empty;
        TxtAmount.Text = string.Empty;
        TxtNote.Text = string.Empty;
    }

    private void Filter_OnClick(object sender, RoutedEventArgs e) => Load();

    private void Load()
    {
        _rows.Clear();
        var from = DtFrom.SelectedDate ?? DateTime.MinValue;
        var to = DtTo.SelectedDate ?? DateTime.MaxValue;
        foreach (var x in _store.ListAccountTransfers(from, to)) _rows.Add(x);
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not AccountTransfer at) return;
        if (MessageBox.Show($"Hapus mutasi dari {at.FromAccount} ke {at.ToAccount}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteAccountTransfer(at.Id);
        Load();
    }
}
