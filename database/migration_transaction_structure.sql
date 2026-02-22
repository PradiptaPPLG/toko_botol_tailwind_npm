-- Migration: Transaction Structure with Header-Detail
-- Date: 2026-02-21
-- Purpose: Separate transaction header (struk/invoice) from transaction details (items)

-- ==============================================
-- 1. CREATE NEW TABLE: transaksi_header
-- ==============================================
-- This table stores the transaction header (invoice/struk)
CREATE TABLE IF NOT EXISTS `transaksi_header` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `no_invoice` VARCHAR(50) NOT NULL,
  `cabang_id` INT(11) NOT NULL,
  `session_kasir_id` INT(11) DEFAULT NULL,
  `nama_kasir` VARCHAR(100) NOT NULL,
  `tipe` ENUM('pembeli','penjual') NOT NULL DEFAULT 'pembeli',
  `total_items` INT(11) DEFAULT 0,
  `total_harga` INT(11) NOT NULL DEFAULT 0,
  `total_bayar` INT(11) DEFAULT NULL,
  `kembalian` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_invoice` (`no_invoice`),
  KEY `cabang_id` (`cabang_id`),
  KEY `session_kasir_id` (`session_kasir_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_tipe` (`tipe`),
  CONSTRAINT `transaksi_header_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`),
  CONSTRAINT `transaksi_header_ibfk_2` FOREIGN KEY (`session_kasir_id`) REFERENCES `session_kasir` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================
-- 2. CREATE NEW TABLE: transaksi_detail
-- ==============================================
-- This table stores individual items in the transaction
CREATE TABLE IF NOT EXISTS `transaksi_detail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaksi_header_id` INT(11) NOT NULL,
  `no_invoice` VARCHAR(50) NOT NULL,
  `produk_id` INT(11) NOT NULL,
  `nama_produk` VARCHAR(100) NOT NULL,
  `jumlah` INT(11) NOT NULL,
  `satuan` VARCHAR(20) NOT NULL DEFAULT 'botol',
  `harga_satuan` INT(11) NOT NULL,
  `harga_tawar` INT(11) DEFAULT NULL,
  `selisih` INT(11) DEFAULT NULL,
  `subtotal` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaksi_header_id` (`transaksi_header_id`),
  KEY `no_invoice` (`no_invoice`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`transaksi_header_id`) REFERENCES `transaksi_header` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================
-- 3. MIGRATE EXISTING DATA (if transaksi table has data)
-- ==============================================
-- This will move data from old transaksi table to new structure
-- IMPORTANT: Run this ONLY ONCE after creating the new tables

-- Insert unique invoices into transaksi_header
INSERT INTO `transaksi_header` (
  `no_invoice`,
  `cabang_id`,
  `session_kasir_id`,
  `nama_kasir`,
  `tipe`,
  `total_harga`,
  `created_at`
)
SELECT
  t.no_invoice,
  t.cabang_id,
  t.session_kasir_id,
  t.nama_kasir,
  t.tipe,
  SUM(t.total_harga) as total_harga,
  MIN(t.created_at) as created_at
FROM `transaksi` t
GROUP BY t.no_invoice, t.cabang_id, t.session_kasir_id, t.nama_kasir, t.tipe
ON DUPLICATE KEY UPDATE no_invoice = no_invoice; -- Skip if already exists

-- Insert transaction details
INSERT INTO `transaksi_detail` (
  `transaksi_header_id`,
  `no_invoice`,
  `produk_id`,
  `nama_produk`,
  `jumlah`,
  `satuan`,
  `harga_satuan`,
  `harga_tawar`,
  `selisih`,
  `subtotal`,
  `created_at`
)
SELECT
  th.id as transaksi_header_id,
  t.no_invoice,
  t.produk_id,
  p.nama_produk,
  t.jumlah,
  t.satuan,
  t.harga_satuan,
  t.harga_tawar,
  t.selisih,
  t.total_harga as subtotal,
  t.created_at
FROM `transaksi` t
JOIN `transaksi_header` th ON t.no_invoice = th.no_invoice
JOIN `produk` p ON t.produk_id = p.id
WHERE NOT EXISTS (
  SELECT 1 FROM `transaksi_detail` td
  WHERE td.no_invoice = t.no_invoice
  AND td.produk_id = t.produk_id
  AND td.created_at = t.created_at
);

-- Update total_items in transaksi_header
# noinspection SqlWithoutWhere
UPDATE `transaksi_header` th
SET th.total_items = (
  SELECT COUNT(*)
  FROM `transaksi_detail` td
  WHERE td.transaksi_header_id = th.id
);

-- ==============================================
-- 4. BACKUP OLD TABLE (OPTIONAL - Recommended)
-- ==============================================
-- Rename old table instead of dropping it (SAFE!)
-- Uncomment the line below after verifying data migration
-- RENAME TABLE `transaksi` TO `transaksi_old_backup`;

-- ==============================================
-- 5. VERIFICATION QUERIES
-- ==============================================
-- Check if migration successful
SELECT
  'Transaction Headers' as table_name,
  COUNT(*) as total_records
FROM transaksi_header
UNION ALL
SELECT
  'Transaction Details' as table_name,
  COUNT(*) as total_records
FROM transaksi_detail;

-- Compare old vs. new
-- SELECT COUNT (DISTINCT no_invoice) as old_invoices FROM transaksi;
-- SELECT COUNT(*) as new_invoices FROM transaksi_header;

-- ==============================================
-- NOTES:
-- ==============================================
-- 1. transaksi_header = Invoice/Struk (one per transaction)
-- 2. transaksi_detail = Items in the transaction (multiple rows)
-- 3. Old 'transaksi' table is kept as backup (rename when ready)
-- 4. New structure allows easy struk printing and reporting
-- 5. Laporan can now GROUP BY invoice with expandable details
-- ==============================================
