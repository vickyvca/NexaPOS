using System;
using System.IO;
using Microsoft.EntityFrameworkCore;

namespace POS.Storage;

public class PosDbContext : DbContext
{
    public DbSet<ProductEntity> Products => Set<ProductEntity>();
    public DbSet<SupplierEntity> Suppliers => Set<SupplierEntity>();
    public DbSet<ItemTypeEntity> ItemTypes => Set<ItemTypeEntity>();
    public DbSet<WarehouseEntity> Warehouses => Set<WarehouseEntity>();
    public DbSet<StockEntity> Stocks => Set<StockEntity>();
    public DbSet<CustomerEntity> Customers => Set<CustomerEntity>();
    public DbSet<SalesmanEntity> Salesmen => Set<SalesmanEntity>();
    public DbSet<BankAccountEntity> BankAccounts => Set<BankAccountEntity>();
    public DbSet<EdcMachineEntity> EdcMachines => Set<EdcMachineEntity>();
    public DbSet<AppSettingEntity> AppSettings => Set<AppSettingEntity>();
    public DbSet<SaleEntity> Sales => Set<SaleEntity>();
    public DbSet<SaleItemEntity> SaleItems => Set<SaleItemEntity>();
    public DbSet<PurchaseEntity> Purchases => Set<PurchaseEntity>();
    public DbSet<PurchaseItemEntity> PurchaseItems => Set<PurchaseItemEntity>();
    public DbSet<PaymentEntity> Payments => Set<PaymentEntity>();
    public DbSet<PayableEntity> Payables => Set<PayableEntity>();
    public DbSet<ExpenseEntity> Expenses => Set<ExpenseEntity>();
    public DbSet<AccountTransferEntity> AccountTransfers => Set<AccountTransferEntity>();

    public string DbPath { get; }

    public PosDbContext()
    {
        var dir = AppDomain.CurrentDomain.BaseDirectory;
        DbPath = Path.Combine(dir, "pos.sqlite");
    }

    protected override void OnConfiguring(DbContextOptionsBuilder options)
        => options.UseSqlite($"Data Source={DbPath}");

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<ProductEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<ProductEntity>().HasIndex(x => x.Name);
        modelBuilder.Entity<ProductEntity>().HasIndex(x => x.Barcode);
        modelBuilder.Entity<ProductEntity>().HasIndex(x => x.Sku);
        modelBuilder.Entity<SupplierEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<ItemTypeEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<WarehouseEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<StockEntity>().HasKey(x => new { x.ProductId, x.WarehouseId });
        modelBuilder.Entity<StockEntity>().HasIndex(x => new { x.WarehouseId });
        modelBuilder.Entity<AppSettingEntity>().HasKey(x => x.Key);
        modelBuilder.Entity<CustomerEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<SaleEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<SaleItemEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<SaleItemEntity>().HasIndex(x => x.SaleId);
        modelBuilder.Entity<PurchaseEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<PurchaseItemEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<PurchaseItemEntity>().HasIndex(x => x.PurchaseId);
        modelBuilder.Entity<PaymentEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<PayableEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<ExpenseEntity>().HasKey(x => x.Id);
        modelBuilder.Entity<AccountTransferEntity>().HasKey(x => x.Id);
    }
}
