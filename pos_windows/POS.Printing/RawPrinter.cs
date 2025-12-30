using System.ComponentModel;
using System.Runtime.InteropServices;
using System.Text;

namespace POS.Printing;

public static class RawPrinter
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private class DOCINFO
    {
        [MarshalAs(UnmanagedType.LPWStr)]
        public string? pDocName;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string? pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string? pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true)]
    static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);

    [DllImport("winspool.Drv", SetLastError = true, ExactSpelling = true)]
    static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true)]
    static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In] DOCINFO di);

    [DllImport("winspool.Drv", SetLastError = true, ExactSpelling = true)]
    static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true, ExactSpelling = true)]
    static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true, ExactSpelling = true)]
    static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true, ExactSpelling = true)]
    static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

    public static void Print(string printerName, byte[] bytes)
    {
        if (!OpenPrinter(printerName, out var hPrinter, IntPtr.Zero))
            throw new Win32Exception(Marshal.GetLastWin32Error(), "OpenPrinter gagal");
        try
        {
            var di = new DOCINFO { pDocName = "POS Receipt", pDataType = "RAW" };
            if (!StartDocPrinter(hPrinter, 1, di))
                throw new Win32Exception(Marshal.GetLastWin32Error(), "StartDocPrinter gagal");
            try
            {
                if (!StartPagePrinter(hPrinter))
                    throw new Win32Exception(Marshal.GetLastWin32Error(), "StartPagePrinter gagal");
                try
                {
                    var pUnmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);
                    try
                    {
                        Marshal.Copy(bytes, 0, pUnmanagedBytes, bytes.Length);
                        if (!WritePrinter(hPrinter, pUnmanagedBytes, bytes.Length, out var written) || written != bytes.Length)
                            throw new Win32Exception(Marshal.GetLastWin32Error(), "WritePrinter gagal");
                    }
                    finally { Marshal.FreeCoTaskMem(pUnmanagedBytes); }
                }
                finally { EndPagePrinter(hPrinter); }
            }
            finally { EndDocPrinter(hPrinter); }
        }
        finally { ClosePrinter(hPrinter); }
    }

    public static byte[] BuildTestReceipt(bool is80mm)
    {
        var sb = new List<byte>();
        // Initialize
        sb.AddRange(new byte[] { 0x1B, 0x40 });
        // Center
        sb.AddRange(new byte[] { 0x1B, 0x61, 0x01 });
        sb.AddRange(Encoding.ASCII.GetBytes("TES PRINT\nPOS Windows\n"));
        sb.AddRange(Encoding.ASCII.GetBytes(new string('-', is80mm ? 32 : 24) + "\n"));
        // Left
        sb.AddRange(new byte[] { 0x1B, 0x61, 0x00 });
        sb.AddRange(Encoding.ASCII.GetBytes("Item A    1 x 1000     1000\n"));
        sb.AddRange(Encoding.ASCII.GetBytes("TOTAL                 1000\n"));
        sb.AddRange(Encoding.ASCII.GetBytes("Terima kasih\n"));
        // Cut (partial)
        sb.AddRange(new byte[] { 0x1D, 0x56, 0x42, 0x20 });
        return sb.ToArray();
    }
}
