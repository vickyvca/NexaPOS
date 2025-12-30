using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class CustomerEditorWindow : Window
{
    public Customer Customer { get; private set; }

    public CustomerEditorWindow(Customer customer)
    {
        InitializeComponent();
        Customer = customer;
        TxtName.Text = customer.Name;
        TxtPhone.Text = customer.Phone;
        TxtMemberCode.Text = customer.MemberCode;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        Customer = Customer with
        {
            Name = TxtName.Text,
            Phone = TxtPhone.Text,
            MemberCode = TxtMemberCode.Text
        };
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
