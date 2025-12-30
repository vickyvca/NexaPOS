using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Storage;
using POS.App;

namespace POS.App.Views;

public partial class ProductPickerWindow : Window
{
    private readonly IStorage _store = StorageService.Current;
    public object? Picked { get; private set; }
    public ProductPickerWindow()
    {
        InitializeComponent();
        Reload();
    }

    private void Reload()
    {
        Grid.ItemsSource = _store.Products(TxtSearch.Text.Trim());
    }

    private void Search_OnClick(object sender, RoutedEventArgs e) => Reload();

    private void Pick_OnClick(object sender, RoutedEventArgs e)
    {
        Picked = Grid.SelectedItem;
        DialogResult = Picked != null;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }

    private void Grid_OnMouseDoubleClick(object sender, System.Windows.Input.MouseButtonEventArgs e)
    {
        Pick_OnClick(sender, e);
    }
}
