using System.Text;

namespace POS.Printing;

public static class BarcodeLabelBuilder
{
    // labels: (barcode, name, qty, price)
    public static byte[] Build(IEnumerable<(string code, string name, int qty, int price)> labels, bool is80mm)
    {
        var width = is80mm ? 32 : 24;
        var bytes = new List<byte>();
        bytes.AddRange(new byte[] { 0x1B, 0x40 }); // init
        foreach (var l in labels)
        {
            for (int i = 0; i < Math.Max(1, l.qty); i++)
            {
                // Center
                bytes.AddRange(new byte[] { 0x1B, 0x61, 0x01 });
                bytes.AddRange(AsciiLine(l.name.Length > width ? l.name.Substring(0, width) : l.name));
                bytes.AddRange(AsciiLine($"Rp {l.price}"));
                // Barcode (CODE128) GS k
                bytes.Add(0x1D); bytes.Add(0x6B); bytes.Add(0x49); // CODE128 auto
                var codeBytes = Encoding.ASCII.GetBytes(l.code);
                bytes.Add((byte)codeBytes.Length);
                bytes.AddRange(codeBytes);
                bytes.Add(0x0A);
                // Left align
                bytes.AddRange(new byte[] { 0x1B, 0x61, 0x00 });
                bytes.AddRange(AsciiLine(l.code));
                bytes.AddRange(AsciiLine(new string('-', width)));
            }
        }
        // Cut
        bytes.AddRange(new byte[] { 0x1D, 0x56, 0x42, 0x20 });
        return bytes.ToArray();
    }

    private static byte[] AsciiLine(string s) => Encoding.ASCII.GetBytes(s + "\n");
}
