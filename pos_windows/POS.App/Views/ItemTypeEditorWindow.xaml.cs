using System.Windows;
using POS.Core;

namespace POS.App.Views;

public partial class ItemTypeEditorWindow : Window
{
    public ItemType ItemType { get; private set; }

    public ItemTypeEditorWindow(ItemType itemType)
    {
        InitializeComponent();
        ItemType = itemType;
        TxtCode.Text = itemType.Code;
        TxtName.Text = itemType.Name;
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        ItemType = ItemType with
        {
            Code = TxtCode.Text,
            Name = TxtName.Text
        };
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
