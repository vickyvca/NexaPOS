using System.Linq;
using System.Text;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class SalesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    public SalesView()
    {
        InitializeComponent();
        LoadRows();
    }

    private void LoadRows()
    {
        Grid.ItemsSource = _store.LoadSales().OrderByDescending(s => s.Date).ToList();
    }

    private void Refresh_OnClick(object sender, RoutedEventArgs e) => LoadRows();

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button btn && btn.DataContext is Sale s)
        {
            if (MessageBox.Show($"Hapus penjualan {s.Number}? Stok akan dikembalikan.", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) == MessageBoxResult.Yes)
            {
                _store.DeleteSale(s.Id);
                LoadRows();
            }
        }
    }

    private void Detail_OnClick(object sender, RoutedEventArgs e)
    {
        if (sender is Button btn && btn.DataContext is Sale s)
        {
            var sb = new StringBuilder();
            sb.AppendLine($"Nomor: {s.Number}");
            sb.AppendLine($"Tanggal: {s.Date:dd/MM/yyyy HH:mm}");
            sb.AppendLine($"Customer: {s.CustomerName}");
            sb.AppendLine($"Salesman: {s.Salesman}");
            sb.AppendLine();
            sb.AppendLine("Item:");
            foreach (var item in s.Items)
            {
                sb.AppendLine($"- {item.Name} ({item.Qty} x {item.Price:N0} = {item.Total:N0})");
            }
            sb.AppendLine();
            sb.AppendLine($"Subtotal: {s.Subtotal:N0}");
            sb.AppendLine($"Pajak: {s.Tax:N0}");
            sb.AppendLine($"Total: {s.Total:N0}");

            MessageBox.Show(sb.ToString(), "Detail Penjualan");
        }
    }
}
