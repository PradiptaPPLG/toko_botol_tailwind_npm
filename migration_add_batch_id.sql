-- Migration: Add batch_id column to stok_masuk and stok_keluar tables
-- This enables batch processing of warehouse stock operations
-- Run this SQL in your database (kasir_toko)

-- Add batch_id to stok_masuk table
ALTER TABLE `stok_masuk`
ADD COLUMN `batch_id` VARCHAR(50) NULL AFTER `keterangan`;

-- Add batch_id to stok_keluar table
ALTER TABLE `stok_keluar`
ADD COLUMN `batch_id` VARCHAR(50) NULL AFTER `keterangan`;

-- Add indexes for better query performance
ALTER TABLE `stok_masuk`
ADD INDEX `idx_batch_id` (`batch_id`);

ALTER TABLE `stok_keluar`
ADD INDEX `idx_batch_id` (`batch_id`);
