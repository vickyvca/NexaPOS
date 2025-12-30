using POS.Storage;

namespace POS.App;

public static class StorageService
{
    private static IStorage? _current;
    // Default: gunakan SQLite (EF)
    public static IStorage Current => _current ??= new EfStorage();
    public static void Use(IStorage storage) => _current = storage;
}
