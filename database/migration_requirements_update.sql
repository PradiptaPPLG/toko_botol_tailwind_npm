-- Migration for Requirement Update
-- Run this SQL in your database (kasir_toko)
-- Date: 2026-02-21
-- SAFE MIGRATION: Uses IF NOT EXISTS checks

-- ==============================================
-- 1. STOK MASUK - Add total belanja tracking
-- ==============================================
ALTER TABLE `stok_masuk`
    ADD COLUMN IF NOT EXISTS `harga_beli_satuan` INT(11) DEFAULT 0 AFTER `jumlah`,
    ADD COLUMN IF NOT EXISTS `total_belanja` INT(11) DEFAULT 0 AFTER `harga_beli_satuan`;

-- ==============================================
-- 2. TRANSAKSI - Add movement tracking
-- ==============================================
ALTER TABLE `transaksi`
    ADD COLUMN IF NOT EXISTS `asal` VARCHAR(100) DEFAULT NULL AFTER `cabang_id`,
    ADD COLUMN IF NOT EXISTS `tujuan` VARCHAR(100) DEFAULT NULL AFTER `asal`;

-- ==============================================
-- 3. STOCK OPNAME - Add cancel functionality
-- ==============================================
ALTER TABLE `stock_opname`
    ADD COLUMN IF NOT EXISTS `is_cancelled` TINYINT(1) DEFAULT 0 AFTER `status`;

-- ==============================================
-- 4. PRODUK - Add SOFT DELETE (SAFE DELETE)
-- NEVER use DELETE query! Always UPDATE status='deleted'
-- ==============================================
ALTER TABLE `produk`
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'deleted') DEFAULT 'active' AFTER `updated_at`,
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL AFTER `status`;

-- ==============================================
-- 5. Add Indexes for Performance
-- ==============================================
-- Check if index exists before creating
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
               WHERE table_schema = DATABASE()
               AND table_name = 'stok_masuk'
               AND index_name = 'idx_total_belanja');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `stok_masuk` ADD INDEX `idx_total_belanja` (`total_belanja`)',
    'SELECT "Index idx_total_belanja already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
               WHERE table_schema = DATABASE()
               AND table_name = 'transaksi'
               AND index_name = 'idx_movement');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `transaksi` ADD INDEX `idx_movement` (`asal`, `tujuan`)',
    'SELECT "Index idx_movement already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
               WHERE table_schema = DATABASE()
               AND table_name = 'produk'
               AND index_name = 'idx_status');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `produk` ADD INDEX `idx_status` (`status`)',
    'SELECT "Index idx_status already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- ==============================================
-- 6. Set default status for existing products
-- ==============================================
UPDATE `produk` SET `status` = 'active' WHERE `status` IS NULL OR `status` = '';

-- ==============================================
-- 7. Verification Queries (Optional - Comment out if not needed)
-- ==============================================
-- SELECT 'Migration completed successfully!' as status;
-- SELECT COUNT(*) as total_active_products FROM produk WHERE status = 'active';
-- SELECT COUNT(*) as total_deleted_products FROM produk WHERE status = 'deleted';

-- ==============================================
-- NOTES:
-- ==============================================
-- 1. This migration is SAFE to run multiple times
-- 2. IF NOT EXISTS prevents duplicate column errors
-- 3. Index checks prevent duplicate index errors
-- 4. NO DELETE queries used - only soft delete with UPDATE
-- 5. All existing products will have status='active'
-- ==============================================
