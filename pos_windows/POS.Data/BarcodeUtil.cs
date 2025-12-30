using System;

namespace POS.Data;

public static class BarcodeUtil
{
    // Generate a random EAN-13 with valid checksum.
    public static string GenerateEAN13()
    {
        var rnd = new Random();
        var digits = new int[13];
        for (int i = 0; i < 12; i++) digits[i] = rnd.Next(0, 10);
        // checksum
        int sum = 0;
        for (int i = 0; i < 12; i++)
        {
            var weight = (i % 2 == 0) ? 1 : 3;
            sum += digits[i] * weight;
        }
        int check = (10 - (sum % 10)) % 10;
        digits[12] = check;
        return string.Concat(digits);
    }
}
