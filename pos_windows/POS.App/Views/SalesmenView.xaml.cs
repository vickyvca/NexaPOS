using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class SalesmenView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Salesman> _rows = new();

    public SalesmenView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var s in _store.Salesmen()) _rows.Add(s);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newSalesman = new Salesman(Guid.NewGuid().ToString("N"), "", "");
        var editor = new SalesmanEditorWindow(newSalesman);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertSalesman(editor.Salesman);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Salesman s) return;
        var editor = new SalesmanEditorWindow(s);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertSalesman(editor.Salesman);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Salesman s) return;
        if (MessageBox.Show($"Hapus salesman {s.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteSalesman(s.Id);
        Reload();
    }
}
