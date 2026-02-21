# Database Migration Instructions

## ⚠️ You Have TWO Migration Files:

### Option 1: `migration_requirements_update.sql` (MySQL 5.7+/MariaDB 10.2+)
**For newer MySQL versions** - Uses `IF NOT EXISTS` syntax
- ✅ **Pros**: Fully automatic, safe to run multiple times
- ⚠️ **Cons**: Requires MySQL 5.7+ or MariaDB 10.2+

### Option 2: `migration_requirements_update_safe.sql` (All MySQL versions)
**For older MySQL/all versions** - Manual section-by-section
- ✅ **Pros**: Works with ALL MySQL/MariaDB versions
- ⚠️ **Cons**: Must run section by section, skip duplicate errors manually

---

## 🚀 How to Run Migration (Choose ONE method):

### Method A: Using `migration_requirements_update.sql` (Newer MySQL)

1. **Open phpMyAdmin**
2. **Select database**: `kasir_toko`
3. **Click "Import" tab**
4. **Choose file**: `migration_requirements_update.sql`
5. **Click "Go"**
6. **Done!** ✅

**If you get errors**, use Method B instead.

---

### Method B: Using `migration_requirements_update_safe.sql` (All versions)

1. **Open phpMyAdmin**
2. **Select database**: `kasir_toko`
3. **Click "SQL" tab**
4. **Copy Section 1** from the file:
   ```sql
   ALTER TABLE `stok_masuk`
       ADD COLUMN `harga_beli_satuan` INT(11) DEFAULT 0 AFTER `jumlah`;
   ```
5. **Paste and Execute**
6. **If you get "Duplicate column name" error**: ✅ **This is OK! Skip to next section**
7. **If successful**: ✅ **Continue to next section**
8. **Repeat steps 4-7** for each section

---

## 📋 What Will Be Added:

### 1. `stok_masuk` table:
- `harga_beli_satuan` - Price per unit when buying stock
- `total_belanja` - Total purchase amount
- Index for performance

### 2. `transaksi` table:
- `asal` - Source of goods (Gudang/Cabang)
- `tujuan` - Destination (Pembeli/Penjual)
- Index for tracking

### 3. `stock_opname` table:
- `is_cancelled` - Flag for cancelled stock opname records

### 4. `produk` table (SOFT DELETE):
- `status` - 'active' or 'deleted' (NO DELETE QUERIES!)
- `deleted_at` - When product was soft deleted
- Index for filtering

---

## ✅ Verification:

After migration, run this query to check:

```sql
SELECT COUNT(*) as total_active_products FROM produk WHERE status = 'active';
```

You should see all your products with `status = 'active'`.

---

## 🛡️ Safety Features:

1. ✅ **No data loss** - Only adds columns, never removes
2. ✅ **No DELETE queries** - Uses soft delete with UPDATE
3. ✅ **Backwards compatible** - Old code will still work
4. ✅ **Safe to run multiple times** (Option 1) or skip duplicates (Option 2)

---

## ⚠️ Common Errors & Solutions:

### Error: "Duplicate column name 'status'"
**Solution**: ✅ This is OK! The column already exists. Skip that ALTER TABLE.

### Error: "Duplicate key name 'idx_status'"
**Solution**: ✅ This is OK! The index already exists. Skip that CREATE INDEX.

### Error: "Unknown column 'status' in 'field list'"
**Solution**: ❌ Migration not run yet. Run the migration first!

---

## 📞 Need Help?

If migration fails:
1. Check which step failed
2. Note the error message
3. That column/index might already exist (which is OK!)
4. Continue with next section

---

## 🎯 After Migration:

1. ✅ Test product deletion in "Tambah Produk" page
2. ✅ Verify deleted products don't show in lists
3. ✅ Check database: deleted products have `status='deleted'`
4. ✅ NO DELETE QUERIES are ever used! 🛡️

---

**Remember**: The migration is designed to be SAFE. Duplicate column errors are expected if you run it twice!
