using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class CustomersView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Customer> _rows = new();

    public CustomersView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var c in _store.Customers()) _rows.Add(c);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newCustomer = new Customer(Guid.NewGuid().ToString("N"), "", "", "");
        var editor = new CustomerEditorWindow(newCustomer);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertCustomer(editor.Customer);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Customer c) return;
        var editor = new CustomerEditorWindow(c);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertCustomer(editor.Customer);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Customer c) return;
        if (MessageBox.Show($"Hapus pelanggan {c.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteCustomer(c.Id);
        Reload();
    }
}
