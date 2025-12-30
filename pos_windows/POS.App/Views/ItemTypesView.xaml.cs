using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ItemTypesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<ItemType> _rows = new();

    public ItemTypesView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var t in _store.ItemTypes()) _rows.Add(t);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newItemType = new ItemType(Guid.NewGuid().ToString("N"), "", "");
        var editor = new ItemTypeEditorWindow(newItemType);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertItemType(editor.ItemType);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not ItemType t) return;
        var editor = new ItemTypeEditorWindow(t);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertItemType(editor.ItemType);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not ItemType t) return;
        if (MessageBox.Show($"Hapus jenis barang {t.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteItemType(t.Id);
        Reload();
    }
}
