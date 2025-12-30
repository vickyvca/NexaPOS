using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class SalesmanEditorWindow : Window
{
    public Salesman Salesman { get; private set; }

    public SalesmanEditorWindow(Salesman salesman)
    {
        InitializeComponent();
        Salesman = salesman;
        TxtName.Text = salesman.Name;
        TxtPhone.Text = salesman.Phone;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        Salesman = Salesman with
        {
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
