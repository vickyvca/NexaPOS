using System.Windows;
using System.Windows.Controls;
using POS.Data;

namespace POS.App.Views;

public partial class LoginView : Page
{
    private readonly MainWindow _mw;
    private readonly JsonDatabase _db;

    public LoginView(MainWindow mw)
    {
        InitializeComponent();
        _mw = mw;
        _db = new JsonDatabase();
    }

    private void Login_OnClick(object sender, RoutedEventArgs e)
    {
        try
        {
            if (_db.Login(TxtUser.Text.Trim(), TxtPass.Password))
            {
                _mw.NavigateToHome();
            }
            else
            {
                LblMsg.Text = "Login gagal. Periksa kembali username dan password.";
            }
        }
        catch (Exception ex)
        {
            LblMsg.Text = $"Error: {ex.Message}";
        }
    }
}
