-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 02:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kasir_toko`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$zb0s6bb7tnCkBP4L25V5d.6k6LiU.9.Ndf3G/WRZ3Eps8gZmgc.O6', 'Admin Utama');

-- --------------------------------------------------------

--
-- Table structure for table `cabang`
--

CREATE TABLE `cabang` (
  `id` int(11) NOT NULL,
  `nama_cabang` varchar(50) NOT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabang`
--

INSERT INTO `cabang` (`id`, `nama_cabang`, `alamat`) VALUES
(1, 'Barat', 'Jl. Raya Barat No.1'),
(2, 'Pusat', 'Jl. Raya Pusat No.2'),
(3, 'Timur', 'Jl. Raya Timur No.3');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `nominal` int(11) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'botol',
  `harga_beli` int(11) NOT NULL DEFAULT 0,
  `harga_jual` int(11) NOT NULL DEFAULT 0,
  `harga_dus` int(11) NOT NULL DEFAULT 0,
  `stok_gudang` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `kode_produk`, `nama_produk`, `satuan`, `harga_beli`, `harga_jual`, `harga_dus`, `stok_gudang`, `created_at`, `updated_at`) VALUES
(1, 'BTL001', 'Botol Kaca 330ml', 'botol', 1500, 3000, 72000, 100, '2026-02-12 19:58:07', '2026-02-12 19:58:07'),
(2, 'BTL002', 'Botol Plastik 500ml', 'botol', 800, 2000, 48000, 100, '2026-02-12 19:58:07', '2026-02-12 19:58:07'),
(3, 'BTL003', 'Botol Besar 1L', 'botol', 2500, 5000, 120000, 50, '2026-02-12 19:58:07', '2026-02-12 19:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_login_kasir`
--

CREATE TABLE `riwayat_login_kasir` (
  `id` int(11) NOT NULL,
  `nama_kasir` varchar(100) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_login_kasir`
--

INSERT INTO `riwayat_login_kasir` (`id`, `nama_kasir`, `cabang_id`, `login_time`) VALUES
(1, 'Pradipta', 2, '2026-02-12 20:12:47');

-- --------------------------------------------------------

--
-- Table structure for table `session_kasir`
--

CREATE TABLE `session_kasir` (
  `id` int(11) NOT NULL,
  `nama_kasir` varchar(100) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `status` enum('login','logout') NOT NULL DEFAULT 'login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_kasir`
--

INSERT INTO `session_kasir` (`id`, `nama_kasir`, `cabang_id`, `login_time`, `logout_time`, `status`) VALUES
(1, 'Pradipta', 2, '2026-02-12 20:12:47', '2026-02-12 20:26:58', 'logout');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname`
--

CREATE TABLE `stock_opname` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `stok_sistem` int(11) NOT NULL,
  `stok_fisik` int(11) NOT NULL,
  `selisih` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'HILANG',
  `petugas` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_cabang`
--

CREATE TABLE `stok_cabang` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_cabang`
--

INSERT INTO `stok_cabang` (`id`, `produk_id`, `cabang_id`, `stok`) VALUES
(1, 1, 1, 30),
(2, 1, 2, 30),
(3, 1, 3, 30),
(4, 2, 1, 30),
(5, 2, 2, 30),
(6, 2, 3, 30),
(7, 3, 1, 15),
(8, 3, 2, 15),
(9, 3, 3, 15);

-- --------------------------------------------------------

--
-- Table structure for table `stok_keluar`
--

CREATE TABLE `stok_keluar` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `kondisi` enum('rusak','transfer') NOT NULL,
  `cabang_tujuan` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_masuk`
--

CREATE TABLE `stok_masuk` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `no_invoice` varchar(50) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `cabang_id` int(11) NOT NULL,
  `session_kasir_id` int(11) DEFAULT NULL,
  `nama_kasir` varchar(100) NOT NULL,
  `tipe` enum('pembeli','penjual') NOT NULL DEFAULT 'pembeli',
  `jumlah` int(11) NOT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'botol',
  `harga_satuan` int(11) NOT NULL,
  `harga_tawar` int(11) DEFAULT NULL,
  `selisih` int(11) DEFAULT NULL,
  `total_harga` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cabang`
--
ALTER TABLE `cabang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`);

--
-- Indexes for table `riwayat_login_kasir`
--
ALTER TABLE `riwayat_login_kasir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- Indexes for table `session_kasir`
--
ALTER TABLE `session_kasir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- Indexes for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `stok_cabang`
--
ALTER TABLE `stok_cabang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produk_cabang` (`produk_id`,`cabang_id`),
  ADD KEY `cabang_id` (`cabang_id`);

--
-- Indexes for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `cabang_tujuan` (`cabang_tujuan`);

--
-- Indexes for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `cabang_id` (`cabang_id`),
  ADD KEY `session_kasir_id` (`session_kasir_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cabang`
--
ALTER TABLE `cabang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `riwayat_login_kasir`
--
ALTER TABLE `riwayat_login_kasir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `session_kasir`
--
ALTER TABLE `session_kasir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_opname`
--
ALTER TABLE `stock_opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_cabang`
--
ALTER TABLE `stok_cabang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `riwayat_login_kasir`
--
ALTER TABLE `riwayat_login_kasir`
  ADD CONSTRAINT `riwayat_login_kasir_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`);

--
-- Constraints for table `session_kasir`
--
ALTER TABLE `session_kasir`
  ADD CONSTRAINT `session_kasir_ibfk_1` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`);

--
-- Constraints for table `stock_opname`
--
ALTER TABLE `stock_opname`
  ADD CONSTRAINT `stock_opname_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`);

--
-- Constraints for table `stok_cabang`
--
ALTER TABLE `stok_cabang`
  ADD CONSTRAINT `stok_cabang_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stok_cabang_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stok_keluar`
--
ALTER TABLE `stok_keluar`
  ADD CONSTRAINT `stok_keluar_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `stok_keluar_ibfk_2` FOREIGN KEY (`cabang_tujuan`) REFERENCES `cabang` (`id`);

--
-- Constraints for table `stok_masuk`
--
ALTER TABLE `stok_masuk`
  ADD CONSTRAINT `stok_masuk_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`),
  ADD CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`session_kasir_id`) REFERENCES `session_kasir` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
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
