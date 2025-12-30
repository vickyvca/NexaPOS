using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class BankAccountsView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<BankAccount> _rows = new();

    public BankAccountsView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var b in _store.BankAccounts()) _rows.Add(b);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newBankAccount = new BankAccount(Guid.NewGuid().ToString("N"), "", "", "");
        var editor = new BankAccountEditorWindow(newBankAccount);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertBankAccount(editor.BankAccount);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not BankAccount b) return;
        var editor = new BankAccountEditorWindow(b);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertBankAccount(editor.BankAccount);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not BankAccount b) return;
        if (MessageBox.Show($"Hapus rekening {b.Bank} - {b.Number}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteBankAccount(b.Id);
        Reload();
    }
}
