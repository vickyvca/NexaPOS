using System.Text;
using POS.Core;

namespace POS.Printing;

public static class ReceiptBuilder
{
    public static byte[] BuildSaleReceipt(
        bool is80mm,
        string title,
        string invoiceNumber,
        DateTime date,
        IEnumerable<SaleItem> items,
        int subtotal,
        int discount,
        int tax,
        int total)
    {
        int width = is80mm ? 32 : 24;
        var sb = new List<byte>();
        // Initialize
        sb.AddRange(new byte[] { 0x1B, 0x40 });
        // Center title
        sb.AddRange(new byte[] { 0x1B, 0x61, 0x01 });
        sb.AddRange(AsciiLine(title));
        sb.AddRange(AsciiLine($"No: {invoiceNumber}"));
        sb.AddRange(AsciiLine(date.ToString("yyyy-MM-dd HH:mm")));
        sb.AddRange(AsciiLine(new string('-', width)));
        // Left align
        sb.AddRange(new byte[] { 0x1B, 0x61, 0x00 });
        foreach (var it in items)
        {
            var name = Truncate(it.Name, width);
            sb.AddRange(AsciiLine(name));
            var qtyPrice = $"{it.Qty} x {it.Price}";
            var totalStr = it.Total.ToString();
            sb.AddRange(AsciiLine(Columns(qtyPrice, totalStr, width)));
        }
        sb.AddRange(AsciiLine(new string('-', width)));
        sb.AddRange(AsciiLine(Columns("Subtotal", subtotal.ToString(), width)));
        if (discount != 0) sb.AddRange(AsciiLine(Columns("Diskon", $"-{discount}", width)));
        if (tax != 0) sb.AddRange(AsciiLine(Columns("PPN", tax.ToString(), width)));
        sb.AddRange(AsciiLine(new string('-', width)));
        sb.AddRange(AsciiLine(Columns("TOTAL", total.ToString(), width)));
        sb.AddRange(AsciiLine(new string('-', width)));
        sb.AddRange(new byte[] { 0x1B, 0x61, 0x01 });
        sb.AddRange(AsciiLine("Terima kasih"));
        // Cut
        sb.AddRange(new byte[] { 0x1D, 0x56, 0x42, 0x20 });
        return sb.ToArray();
    }

    private static byte[] AsciiLine(string s) => Encoding.ASCII.GetBytes(s + "\n");

    private static string Columns(string left, string right, int width)
    {
        // one space between columns minimum
        var space = width - left.Length - right.Length;
        if (space < 1) space = 1;
        return left + new string(' ', space) + right;
    }

    private static string Truncate(string s, int width)
    {
        if (s.Length <= width) return s;
        return s.Substring(0, width);
    }
}
