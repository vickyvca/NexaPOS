using System.Windows;
using POS.Core;
using POS.Storage;
using POS.App;

namespace POS.App.Views;

public partial class QuickProductWindow : Window
{
    private readonly IStorage _store = StorageService.Current;
    public Product? Created { get; private set; }
    private readonly Product? _existing;
    public QuickProductWindow(Product? existing = null)
    {
        InitializeComponent();
        _existing = existing;
        // load suppliers & item types
        CmbSupplier.ItemsSource = _store.Suppliers();
        CmbType.ItemsSource = _store.ItemTypes();
        if (_existing != null)
        {
            TxtName.Text = _existing.Name;
            TxtArticle.Text = _existing.Article ?? "";
            TxtSku.Text = _existing.Sku ?? "";
            TxtBarcode.Text = _existing.Barcode ?? "";
            TxtH1.Text = _existing.PriceH1?.ToString() ?? "";
            TxtH2.Text = _existing.PriceH2?.ToString() ?? "";
            TxtG.Text = _existing.PriceGrosir?.ToString() ?? "";
            if (!string.IsNullOrWhiteSpace(_existing.SupplierId)) CmbSupplier.SelectedValue = _existing.SupplierId;
            if (!string.IsNullOrWhiteSpace(_existing.ItemTypeId)) CmbType.SelectedValue = _existing.ItemTypeId;
        }
    }

    private void Save_OnClick(object sender, RoutedEventArgs e)
    {
        if (string.IsNullOrWhiteSpace(TxtName.Text)) { MessageBox.Show("Nama harus diisi"); return; }
        var id = _existing?.Id ?? Guid.NewGuid().ToString("N");
        var supplierId = CmbSupplier.SelectedValue as string;
        var typeId = CmbType.SelectedValue as string;
        var barcode = string.IsNullOrWhiteSpace(TxtBarcode.Text)
            ? (supplierId != null && typeId != null ? _store.GenerateProductBarcode(supplierId, typeId) : null)
            : TxtBarcode.Text.Trim();
        int? H(string s) => int.TryParse(s.Trim(), out var v) ? v : (int?)null;
        var p = new Product(
            id,
            TxtName.Text.Trim(),
            barcode,
            null,
            string.IsNullOrWhiteSpace(TxtSku.Text) ? null : TxtSku.Text.Trim(),
            true,
            H(TxtH1.Text),
            H(TxtH2.Text),
            H(TxtG.Text),
            string.IsNullOrWhiteSpace(TxtArticle.Text) ? null : TxtArticle.Text.Trim(),
            supplierId,
            typeId
        );
        _store.UpsertProduct(p);
        Created = p;
        DialogResult = true;
    }

    private void Cancel_OnClick(object sender, RoutedEventArgs e)
    {
        DialogResult = false;
    }
}
