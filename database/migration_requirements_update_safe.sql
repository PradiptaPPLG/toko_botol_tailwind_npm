-- Migration for Requirements Update - SAFE VERSION
-- Run this SQL in your database (kasir_toko)
-- Date: 2026-02-21
-- Compatible with all MySQL/MariaDB versions

-- ==============================================
-- INSTRUCTIONS:
-- 1. Run each section ONE BY ONE in phpMyAdmin
-- 2. If you get "Duplicate column" error, skip that section
-- 3. Continue with the next section
-- ==============================================

-- ==============================================
-- 1. STOK MASUK - Add total belanja tracking
-- ==============================================
-- Run this first, if error "Duplicate column", skip to next section
ALTER TABLE `stok_masuk`
    ADD COLUMN `harga_beli_satuan` INT(11) DEFAULT 0 AFTER `jumlah`;

ALTER TABLE `stok_masuk`
    ADD COLUMN `total_belanja` INT(11) DEFAULT 0 AFTER `harga_beli_satuan`;

-- ==============================================
-- 2. TRANSAKSI - Add movement tracking
-- ==============================================
-- If error "Duplicate column", skip to next section
ALTER TABLE `transaksi`
    ADD COLUMN `asal` VARCHAR(100) DEFAULT NULL AFTER `cabang_id`;

ALTER TABLE `transaksi`
    ADD COLUMN `tujuan` VARCHAR(100) DEFAULT NULL AFTER `asal`;

-- ==============================================
-- 3. STOCK OPNAME - Add cancel functionality
-- ==============================================
-- If error "Duplicate column", skip to next section
ALTER TABLE `stock_opname`
    ADD COLUMN `is_cancelled` TINYINT(1) DEFAULT 0 AFTER `status`;

-- ==============================================
-- 4. PRODUK - Add SOFT DELETE (SAFE DELETE)
-- NEVER use DELETE query! Always UPDATE status='deleted'
-- ==============================================
-- If error "Duplicate column", skip to next section
ALTER TABLE `produk`
    ADD COLUMN `status` ENUM('active', 'deleted') DEFAULT 'active' AFTER `updated_at`;

ALTER TABLE `produk`
    ADD COLUMN `deleted_at` DATETIME NULL AFTER `status`;

-- ==============================================
-- 5. Add Indexes for Performance
-- ==============================================
-- If error "Duplicate key name", skip that index
ALTER TABLE `stok_masuk`
    ADD INDEX `idx_total_belanja` (`total_belanja`);

ALTER TABLE `transaksi`
    ADD INDEX `idx_movement` (`asal`, `tujuan`);

ALTER TABLE `produk`
    ADD INDEX `idx_status` (`status`);

-- ==============================================
-- 6. Set default status for existing products
-- ==============================================
-- ALWAYS run this - it's safe
UPDATE `produk` SET `status` = 'active' WHERE `status` IS NULL OR `status` = '';

-- ==============================================
-- 7. Verification (run this to check results)
-- ==============================================
SELECT 'Migration completed!' as message;
SELECT COUNT(*) as total_active_products FROM produk WHERE status = 'active';

-- ==============================================
-- NOTES:
-- ==============================================
-- 1. Run each ALTER TABLE separately
-- 2. Skip errors about "Duplicate column name" or "Duplicate key"
-- 3. Those errors mean the column/index already exists (which is OK!)
-- 4. NO DELETE queries used - only safe UPDATE for soft delete
-- ==============================================
