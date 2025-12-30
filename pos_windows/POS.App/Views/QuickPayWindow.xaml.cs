using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using System.Collections.ObjectModel;
using System.ComponentModel;
using POS.Storage;

namespace POS.App.Views;

public partial class QuickPayWindow : Window
{
    private readonly IStorage _store = StorageService.Current;
    private readonly int _total;
    private readonly string _initialMethod;

    public string PaymentMethod { get; private set; } = "";

    private readonly ObservableCollection<PaymentLine> _lines = new();

    public QuickPayWindow(int total, string method)
    {
        InitializeComponent();
        _total = total;
        _initialMethod = method;
        TxtTotal.Text = $"Rp {_total:N0}";
        Grid.ItemsSource = _lines;
        AddInitialRow();
        Recalc();
    }

    private void AddInitialRow()
    {
        var line = new PaymentLine(this) { Method = _initialMethod, Amount = _total };
        _lines.Add(line);
    }

    internal void RefreshOptions(PaymentLine line)
    {
        line.Methods = new ObservableCollection<string>(new[] { "Cash", "Card", "QRIS" });
        if (line.Method == "Card")
            line.Options = new ObservableCollection<string>(_store.EdcMachines().Select(x => x.Name));
        else if (line.Method == "QRIS")
            line.Options = new ObservableCollection<string>(_store.BankAccounts().Select(x => x.Bank));
        else
            line.Options = new ObservableCollection<string>();
    }

    internal void Recalc()
    {
        int sum = _lines.Sum(l => l.Amount);
        int sumCash = _lines.Where(l => l.Method == "Cash").Sum(l => l.Amount);
        int nonCash = sum - sumCash;
        int needFromCash = Math.Max(0, _total - nonCash);
        int change = Math.Max(0, sumCash - needFromCash);
        int remain = Math.Max(0, _total - sum);
        TxtPaid.Text = $"Rp {sum:N0}";
        TxtRemain.Text = $"Rp {remain:N0}";
        TxtChange.Text = $"Rp {change:N0}";
    }

    private void AddRow_OnClick(object sender, RoutedEventArgs e)
        => _lines.Add(new PaymentLine(this) { Method = "Cash", Amount = 0 });

    private void RemoveRow_OnClick(object sender, RoutedEventArgs e)
    {
        if (Grid.SelectedItem is PaymentLine l) _lines.Remove(l);
        else if (_lines.Count > 1) _lines.RemoveAt(_lines.Count - 1);
        Recalc();
    }

    private void Pay_OnClick(object? sender, RoutedEventArgs e)
    {
        int sum = _lines.Sum(l => l.Amount);
        if (sum < _total)
        {
            MessageBox.Show("Total bayar kurang dari tagihan.", "Validasi", MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }
        foreach (var l in _lines)
        {
            if (l.Method == "Card" && string.IsNullOrWhiteSpace(l.Extra)) { MessageBox.Show("Pilih mesin EDC."); return; }
            if (l.Method == "QRIS" && string.IsNullOrWhiteSpace(l.Extra)) { MessageBox.Show("Pilih bank QRIS."); return; }
        }
        var parts = _lines.Select(l => l.Method switch
        {
            "Cash" => $"CASH Rp {l.Amount:N0}",
            "Card" => $"CARD({l.Extra}) Rp {l.Amount:N0}",
            _ => $"QRIS({l.Extra}) Rp {l.Amount:N0}"
        });
        PaymentMethod = _lines.Count > 1 ? ("SPLIT: " + string.Join("; ", parts)) : parts.First();
        DialogResult = true;
    }

    private void Amount_PreviewTextInput(object sender, TextCompositionEventArgs e)
    {
        e.Handled = !e.Text.All(char.IsDigit);
    }
    private void Amount_OnPaste(object sender, DataObjectPastingEventArgs e)
    {
        if (e.DataObject.GetDataPresent(DataFormats.Text))
        {
            var s = e.DataObject.GetData(DataFormats.Text) as string ?? "";
            if (!s.All(char.IsDigit)) e.CancelCommand();
        }
        else e.CancelCommand();
    }
}

public class PaymentLine : INotifyPropertyChanged
{
    private readonly QuickPayWindow _owner;
    public PaymentLine(QuickPayWindow owner)
    {
        _owner = owner;
        Methods = new ObservableCollection<string>(new[] { "Cash", "Card", "QRIS" });
        _owner.RefreshOptions(this);
    }

    private string _method = "Cash";
    public string Method
    {
        get => _method;
        set { if (_method != value) { _method = value; OnPropertyChanged(nameof(Method)); _owner.RefreshOptions(this); _owner.Recalc(); } }
    }

    private int _amount;
    public int Amount
    {
        get => _amount;
        set { if (value != _amount) { _amount = Math.Max(0, value); OnPropertyChanged(nameof(Amount)); _owner.Recalc(); } }
    }

    private string? _extra;
    public string? Extra
    {
        get => _extra;
        set { if (_extra != value) { _extra = value; OnPropertyChanged(nameof(Extra)); _owner.Recalc(); } }
    }

    public ObservableCollection<string> Methods { get; set; } = new();
    public ObservableCollection<string> Options { get; set; } = new();

    public event PropertyChangedEventHandler? PropertyChanged;
    private void OnPropertyChanged(string n) => PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(n));
}