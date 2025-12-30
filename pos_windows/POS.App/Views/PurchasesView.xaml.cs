using System;
using System.Collections.ObjectModel;
using System.Linq;
using System.Text;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class PurchasesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Purchase> _rows = new();

    public PurchasesView()
    {
        InitializeComponent();
        try
        {
            Grid.ItemsSource = _rows;
            Reload();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error initializing view: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Reload()
    {
        try
        {
            _rows.Clear();
            // Show latest first
            var purchases = _store.LoadPurchases()?.OrderByDescending(x => x.Date) ?? Enumerable.Empty<Purchase>();
            foreach (var p in purchases)
            {
                if (p != null)
                {
                    _rows.Add(p);
                }
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error reloading data: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            var win = new PurchaseEditorWindow();
            if (win.ShowDialog() == true)
            {
                Reload();
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error adding purchase: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Detail_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            if (sender is Button btn && btn.DataContext is Purchase p)
            {
                var sb = new StringBuilder();
                sb.AppendLine($"Nomor: {p.Number}");
                sb.AppendLine($"Tanggal: {p.Date:dd/MM/yyyy HH:mm}");
                sb.AppendLine($"Supplier: {p.SupplierName}");
                sb.AppendLine();
                sb.AppendLine("Item:");

                if (p.Items != null)
                {
                    foreach (var item in p.Items)
                    {
                        sb.AppendLine($"- {item.Name} ({item.Qty} x {item.Price:N0} = {item.Total:N0})");
                    }
                }

                sb.AppendLine();
                sb.AppendLine($"Subtotal: {p.Subtotal:N0}");
                sb.AppendLine($"Pajak: {p.Tax:N0}");
                sb.AppendLine($"Total: {p.Total:N0}");
                sb.AppendLine($"Status: {p.PaymentStatus}");

                MessageBox.Show(sb.ToString(), "Detail Pembelian");
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error showing details: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            if (sender is Button btn && btn.DataContext is Purchase p)
            {
                if (MessageBox.Show(
                    $"Hapus pembelian {p.Number}? Stok akan dikembalikan.",
                    "Konfirmasi",
                    MessageBoxButton.YesNo,
                    MessageBoxImage.Warning) == MessageBoxResult.Yes)
                {
                    _store.DeletePurchase(p.Id);
                    Reload();
                }
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Error deleting purchase: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }
}
