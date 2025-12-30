using System.Collections.ObjectModel;
using System.Windows.Controls;
using POS.Core;
using POS.Storage;

namespace POS.App.Views;

public partial class ReportDebtsView : Page
{
    private readonly IStorage _store = StorageService.Current;
    private readonly ObservableCollection<Payable> _rows = new();

    public ReportDebtsView()
    {
        InitializeComponent();
        Grid.ItemsSource = _rows;
        Load();
    }

    private void Load()
    {
        _rows.Clear();
        foreach (var p in _store.Payables())
        {
            _rows.Add(p);
        }
    }
}
