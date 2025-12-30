using System.Linq;
using System.Windows;
using POS.Core;
using POS.Storage;
using POS.App;

namespace POS.App.Views;

public partial class SalePaymentWindow : Window
{
    private readonly IStorage _store = StorageService.Current;
    public string? CustomerId { get; private set; }
    public string? CustomerName { get; private set; }
    public string? Salesman { get; private set; }
    public string PaymentMethod { get; private set; } = "Tunai";

    public SalePaymentWindow()
    {
        InitializeComponent();
        var customers = _store.Customers();
        CmbCustomer.Items.Add("Umum");
        foreach (var c in customers)
        {
            CmbCustomer.Items.Add(c);
        }
        CmbCustomer.SelectedIndex = 0;
        CmbMethod.SelectedIndex = 0;
    }

    private void Pay_OnClick(object sender, RoutedEventArgs e)
    {
        if (CmbCustomer.SelectedItem is Customer c)
        {
            CustomerId = c.Id;
            CustomerName = c.Name;
        }
        else
        {
            CustomerId = null;
            CustomerName = "Umum";
        }
        Salesman = string.IsNullOrWhiteSpace(TxtSalesman.Text) ? null : TxtSalesman.Text.Trim();
        PaymentMethod = ((System.Windows.Controls.ComboBoxItem)CmbMethod.SelectedItem!).Content!.ToString()!;
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
