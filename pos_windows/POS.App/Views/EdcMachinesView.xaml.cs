using System;
using System.Collections.ObjectModel;
using System.Windows;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class EdcMachinesView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<EdcMachine> _rows = new();

    public EdcMachinesView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Reload();
    }

    private void Reload()
    {
        _rows.Clear();
        foreach (var em in _store.EdcMachines()) _rows.Add(em);
    }

    private void Add_OnClick(object sender, RoutedEventArgs e)
    {
        var newEdcMachine = new EdcMachine(Guid.NewGuid().ToString("N"), "", "", "");
        var editor = new EdcMachineEditorWindow(newEdcMachine);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertEdcMachine(editor.EdcMachine);
            Reload();
        }
    }

    private void Edit_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not EdcMachine em) return;
        var editor = new EdcMachineEditorWindow(em);
        if (editor.ShowDialog() == true)
        {
            _store.UpsertEdcMachine(editor.EdcMachine);
            Reload();
        }
    }

    private void Delete_OnClick(object sender, RoutedEventArgs e)
    {
        if (((Button)sender).DataContext is not EdcMachine em) return;
        if (MessageBox.Show($"Hapus mesin EDC {em.Name}?", "Konfirmasi", MessageBoxButton.YesNo, MessageBoxImage.Warning) != MessageBoxResult.Yes) return;
        _store.DeleteEdcMachine(em.Id);
        Reload();
    }
}
