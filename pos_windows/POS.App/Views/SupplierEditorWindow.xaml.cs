using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class SupplierEditorWindow : Window
{
    public Supplier Supplier { get; private set; }

    public SupplierEditorWindow(Supplier supplier)
    {
        InitializeComponent();
        Supplier = supplier;
        TxtCode.Text = supplier.Code;
        TxtName.Text = supplier.Name;
        TxtPhone.Text = supplier.Phone;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        Supplier = Supplier with
        {
            Code = TxtCode.Text,
            Name = TxtName.Text,
            Phone = TxtPhone.Text
        };
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
