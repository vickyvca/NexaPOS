using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class EdcMachineEditorWindow : Window
{
    public EdcMachine EdcMachine { get; private set; }

    public EdcMachineEditorWindow(EdcMachine edcMachine)
    {
        InitializeComponent();
        EdcMachine = edcMachine;
        TxtName.Text = edcMachine.Name;
        TxtBank.Text = edcMachine.Bank;
        TxtLocation.Text = edcMachine.Location;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        EdcMachine = EdcMachine with
        {
            Name = TxtName.Text,
            Bank = TxtBank.Text,
            Location = TxtLocation.Text
        };
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
