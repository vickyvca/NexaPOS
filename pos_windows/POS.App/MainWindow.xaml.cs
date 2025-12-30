using System.Windows;
using System.Windows.Controls;
using System.Linq;
using System.Windows.Media.Imaging;
using POS.App.Views;
using POS.Storage;

namespace POS.App;

public partial class MainWindow : Window
{
    public MainWindow()
    {
        InitializeComponent();
        MainFrame.Content = new LoginView(this);
        RefreshHeader();
    }

    public void NavigateToHome() => MainFrame.Content = new PosView();

    public void RefreshHeader()
    {
        try
        {
            TxtStorage.Text = $"Storage: {(StorageService.Current is POS.Storage.EfStorage ? "SQLite (EF)" : "JSON")}";
            var store = StorageService.Current;
            var name = store.GetSetting("store.name") ?? "Toko Anda";
            TxtStore.Text = name;
            var logoPath = store.GetSetting("store.logo");
            if (!string.IsNullOrWhiteSpace(logoPath) && System.IO.File.Exists(logoPath))
            {
                var bmp = new BitmapImage();
                bmp.BeginInit();
                bmp.UriSource = new System.Uri(logoPath, System.UriKind.Absolute);
                bmp.CacheOption = BitmapCacheOption.OnLoad;
                bmp.EndInit();
                ImgLogo.Source = bmp;
            }
            else
            {
                ImgLogo.Source = null;
            }
        }
        catch { }
    }

    private void BtnPos_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new PosView();
    private void BtnInventory_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new InventoryView();
    private void BtnProduk_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ProductsView();
    private void BtnPrinter_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new PrinterSettingsView();
    private void BtnPembelian_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new PurchasesView();
    private void BtnHutang_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new PayablesView();
    private void BtnSupplier_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new SuppliersView();
    private void BtnCustomer_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new CustomersView();
    private void BtnSales_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new SalesView();
    private void BtnSalesman_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new SalesmenView();
    private void BtnJenis_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ItemTypesView();
    // Kategori dihapus
    private void BtnRekening_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new BankAccountsView();
    private void BtnEdc_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new EdcMachinesView();
    private void BtnReturJual_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new SalesReturnView();
    private void BtnReturBeli_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new PurchaseReturnView();
    private void BtnPembagian_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new StockAllocationView();
    private void BtnMutasi_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new StockTransferView();
    private void BtnOpname_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new StockOpnameView();
    private void BtnPengeluaran_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ExpensesView();
    private void BtnMutasiRek_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new AccountTransferView();
    private void BtnAkuntansi_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new SimpleAccountingView();
    private void BtnTools_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ToolsView();
    private void BtnLapJualTotal_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportSalesTotalView();
    private void BtnLapJualSalesman_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportSalesBySalesmanView();
    private void BtnLapJualSupplier_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportSalesBySupplierView();
    private void BtnLapLR_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportProfitLossView();
    private void BtnLapBeli_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportPurchasesView();
    private void BtnLapHutang_OnClick(object sender, RoutedEventArgs e) => MainFrame.Content = new ReportDebtsView();
}
