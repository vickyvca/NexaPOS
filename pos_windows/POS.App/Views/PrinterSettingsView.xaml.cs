using System.Linq;
using System.Text;
using System.Windows;
using System.Windows.Controls;
using POS.Storage;
using POS.App;
using POS.Printing;

namespace POS.App.Views;

public partial class PrinterSettingsView : Page
{
    private readonly IStorage _store = StorageService.Current;
    public PrinterSettingsView()
    {
        InitializeComponent();
        // Load printers
        foreach (string p in System.Drawing.Printing.PrinterSettings.InstalledPrinters)
        {
            CmbPrinters.Items.Add(p);
        }
        // Load settings
        var paper = _store.GetSetting("printer.paper") ?? "80mm";
        CmbPaper.SelectedIndex = paper == "80mm" ? 1 : 0;
        var name = _store.GetSetting("printer.usb.name");
        if (!string.IsNullOrWhiteSpace(name)) CmbPrinters.SelectedItem = name;
    }

    private void Test_OnClick(object sender, RoutedEventArgs e)
    {
        var name = (string?)CmbPrinters.SelectedItem ?? new System.Drawing.Printing.PrinterSettings().PrinterName;
        var paper = ((ComboBoxItem)CmbPaper.SelectedItem!).Content!.ToString() == "80mm";
        var bytes = RawPrinter.BuildTestReceipt(paper);
        try
        {
            RawPrinter.Print(name, bytes);
            LblMsg.Text = "Tes print dikirim";
        }
        catch (System.Exception ex)
        {
            LblMsg.Text = $"Gagal: {ex.Message}";
        }
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        var name = (string?)CmbPrinters.SelectedItem ?? new System.Drawing.Printing.PrinterSettings().PrinterName;
        _store.SetSetting("printer.backend", "USB");
        _store.SetSetting("printer.usb.name", name);
        var paper = ((ComboBoxItem)CmbPaper.SelectedItem!).Content!.ToString()!;
        _store.SetSetting("printer.paper", paper);
        LblMsg.Text = "Pengaturan disimpan";
    }
}
