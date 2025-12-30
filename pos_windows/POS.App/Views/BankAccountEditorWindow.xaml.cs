using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class BankAccountEditorWindow : Window
{
    public BankAccount BankAccount { get; private set; }

    public BankAccountEditorWindow(BankAccount bankAccount)
    {
        InitializeComponent();
        BankAccount = bankAccount;
        TxtBank.Text = bankAccount.Bank;
        TxtNumber.Text = bankAccount.Number;
        TxtHolder.Text = bankAccount.Holder;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        BankAccount = BankAccount with
        {
            Bank = TxtBank.Text,
            Number = TxtNumber.Text,
            Holder = TxtHolder.Text
        };
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
