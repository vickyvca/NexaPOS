using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ProductsView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Product> _rows = new();

    public ProductsView()
    {
        InitializeComponent();
        ProductsGrid.ItemsSource = _rows;
        Reload();
        ProductsGrid.MouseDoubleClick += (_, __) => EditSelected();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var p in _store.Products(TxtSearch.Text.Trim()))
            _rows.Add(p);
    }

    private void TxtSearch_OnKeyDown(object sender, KeyEventArgs e)
    {
        if (e.Key == Key.Enter) Reload();
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var dlg = new QuickProductWindow();
        if (dlg.ShowDialog() == true && dlg.Created != null)
        {
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Product p) return;
        var dlg = new QuickProductWindow(p);
        if (dlg.ShowDialog() == true)
        {
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not Product p) return;
        if (MessageBox.Show($"Hapus produk {p.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteProduct(p.Id);
        Reload();
    }

    private void EditSelected()
    {
        if (ProductsGrid.SelectedItem is not Product p) return;
        var dlg = new QuickProductWindow(p);
        if (dlg.ShowDialog() == true)
        {
            Reload();
        }
    }
}
