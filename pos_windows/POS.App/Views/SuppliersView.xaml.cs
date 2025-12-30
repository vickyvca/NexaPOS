using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class SuppliersView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Supplier> _rows = new();

    public SuppliersView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var s in _store.Suppliers()) _rows.Add(s);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newSupplier = new Supplier(Guid.NewGuid().ToString("N"), "", "", "");
        var editor = new SupplierEditorWindow(newSupplier);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertSupplier(editor.Supplier);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Supplier s) return;
        var editor = new SupplierEditorWindow(s);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertSupplier(editor.Supplier);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Supplier s) return;
        if (MessageBox.Show($"Hapus supplier {s.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteSupplier(s.Id);
        Reload();
    }
}
