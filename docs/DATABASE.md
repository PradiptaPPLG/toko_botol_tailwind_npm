# 🗄️ Database Schema Documentation

Dokumentasi lengkap skema database Sistem POS Toko Botol.

## Daftar Isi

- [Overview](#overview)
- [Entity Relationship Diagram](#entity-relationship-diagram)
- [Daftar Tabel](#daftar-tabel)
- [Detail Tabel](#detail-tabel)
- [Relasi Antar Tabel](#relasi-antar-tabel)
- [Trigger dan Stored Procedure](#trigger-dan-stored-procedure)

---

## Overview

Database menggunakan **MariaDB 10.x / MySQL 8.0+** dengan character set **utf8mb4** untuk mendukung emoji dan karakter Unicode lengkap.

**Nama Database:** `kasir_toko`

**Total Tabel:** 13 tabel (12 aplikasi + 1 migrations)

---

## Entity Relationship Diagram

```
┌─────────────┐
│   ADMIN     │
└─────────────┘

┌─────────────┐         ┌──────────────────┐         ┌────────────────┐
│   CABANG    │◄────────┤ SESSION_KASIR    │◄────────┤ TRANSAKSI_     │
│             │         │                  │         │ HEADER         │
│ - id        │         │ - id             │         │                │
│ - nama      │         │ - cabang_id      │         │ - id           │
│ - alamat    │         │ - nama_kasir     │         │ - session_id   │
│             │         │ - login_at       │         │ - invoice_no   │
└─────┬───────┘         │ - logout_at      │         │ - tipe         │
      │                 └──────────────────┘         │ - total        │
      │                                              └────────┬───────┘
      │                 ┌──────────────────┐                 │
      └─────────────────┤ RIWAYAT_LOGIN_   │                 │
                        │ KASIR            │                 │
                        │                  │                 │
                        │ - id             │                 │
                        │ - cabang_id      │         ┌───────▼────────┐
                        │ - nama_kasir     │         │ TRANSAKSI_     │
                        │ - login_at       │         │ DETAIL         │
                        └──────────────────┘         │                │
                                                     │ - id           │
┌─────────────┐         ┌──────────────────┐       │ - header_id    │
│   PRODUK    │         │ STOK_CABANG      │       │ - produk_id    │
│             │◄────────┤                  │       │ - qty          │
│ - id        │         │ - id             │       │ - harga        │
│ - kode      │         │ - produk_id      │       └────────┬───────┘
│ - nama      │         │ - cabang_id      │                │
│ - harga     │         │ - jumlah         │                │
│ - status    │         └──────────────────┘                │
└─────┬───────┘                                             │
      │                                                     │
      ├─────────────────┐                                   │
      │                 │                                   │
      │         ┌───────▼────────┐                          │
      │         │ STOK_MASUK     │                          │
      │         │                │                          │
      │         │ - id           │                          │
      │         │ - produk_id    │◄─────────────────────────┘
      │         │ - jumlah       │
      │         │ - batch_id     │
      │         └────────────────┘
      │
      │         ┌────────────────┐
      │         │ STOK_KELUAR    │
      │         │                │
      │         │ - id           │
      ├─────────┤ - produk_id    │
      │         │ - jumlah       │
      │         │ - jenis        │
      │         │ - batch_id     │
      │         └────────────────┘
      │
      │         ┌────────────────┐
      │         │ STOCK_OPNAME   │
      │         │                │
      │         │ - id           │
      └─────────┤ - produk_id    │
                │ - stok_sistem  │
                │ - stok_fisik   │
                │ - status       │
                └────────────────┘

┌─────────────┐
│ PENGELUARAN │
│             │
│ - id        │
│ - keterangan│
│ - nominal   │
│ - tanggal   │
└─────────────┘
```

---

## Daftar Tabel

| No | Tabel                 | Keterangan                                           |
|----|-----------------------|------------------------------------------------------|
| 1  | `admin`               | Akun pengguna admin sistem                           |
| 2  | `cabang`              | Master data cabang toko                              |
| 3  | `produk`              | Master data produk (dengan soft delete)              |
| 4  | `pengeluaran`         | Catatan pengeluaran operasional                      |
| 5  | `session_kasir`       | Session login kasir per shift                        |
| 6  | `riwayat_login_kasir` | Log history login kasir                              |
| 7  | `stok_cabang`         | Level stok produk per cabang                         |
| 8  | `stok_masuk`          | Transaksi stok masuk dari supplier                   |
| 9  | `stok_keluar`         | Transaksi stok keluar (rusak/transfer)               |
| 10 | `stock_opname`        | Catatan stock opname (pemeriksaan fisik)             |
| 11 | `transaksi_header`    | Header invoice transaksi penjualan/pembelian         |
| 12 | `transaksi_detail`    | Detail item per transaksi                            |
| 13 | `migrations`          | Tracking migrasi database (system table)             |

---

## Detail Tabel

### 1. `admin`

Menyimpan data akun admin sistem.

```sql
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'gudang') NOT NULL DEFAULT 'admin',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `username` — Username login (unique)
- `password` — Password (hashed)
- `nama` — Nama lengkap admin
- `role` — Role: admin atau gudang
- `created_at`, `updated_at` — Timestamp

**Default Data:**
- Username: `admin`, Password: `admin`, Role: `admin`

---

### 2. `cabang`

Master data cabang toko.

```sql
CREATE TABLE cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    telepon VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `nama` — Nama cabang (contoh: "Cabang Barat")
- `alamat` — Alamat lengkap cabang
- `telepon` — Nomor telepon cabang
- `created_at`, `updated_at` — Timestamp

**Default Data:**
- Cabang Barat, Cabang Pusat, Cabang Timur

---

### 3. `produk`

Master data produk dengan **soft delete**.

```sql
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(150) NOT NULL,
    harga_beli INT(11) NOT NULL DEFAULT 0,
    harga_jual INT(11) NOT NULL DEFAULT 0,
    stok_gudang INT(11) NOT NULL DEFAULT 0,
    status ENUM('active', 'deleted') NOT NULL DEFAULT 'active',
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_status (status),
    KEY idx_kode (kode_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `kode_produk` — Kode unik produk (contoh: "BTL001")
- `nama_produk` — Nama produk
- `harga_beli` — Harga beli dari supplier (IDR)
- `harga_jual` — Harga jual ke customer (IDR)
- `stok_gudang` — Stok di gudang pusat
- `status` — Status: `active` atau `deleted` (soft delete)
- `deleted_at` — Timestamp soft delete
- `created_at`, `updated_at` — Timestamp

**Catatan:**
- Produk **TIDAK PERNAH** dihapus permanen dari database
- Gunakan `status='active'` untuk filter produk aktif

---

### 4. `pengeluaran`

Catatan pengeluaran operasional toko.

```sql
CREATE TABLE pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keterangan TEXT NOT NULL,
    nominal INT(11) NOT NULL DEFAULT 0,
    tanggal DATE NOT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `keterangan` — Deskripsi pengeluaran
- `nominal` — Jumlah pengeluaran (IDR)
- `tanggal` — Tanggal pengeluaran
- `created_at`, `updated_at` — Timestamp

---

### 5. `session_kasir`

Session login kasir per shift kerja.

```sql
CREATE TABLE session_kasir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT(11) NOT NULL,
    nama_kasir VARCHAR(100) NOT NULL,
    login_at DATETIME NOT NULL,
    logout_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE RESTRICT,
    KEY idx_cabang (cabang_id),
    KEY idx_login (login_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key (session ID)
- `cabang_id` — Foreign key ke tabel `cabang`
- `nama_kasir` — Nama kasir
- `login_at` — Waktu login
- `logout_at` — Waktu logout (NULL jika masih aktif)
- `created_at`, `updated_at` — Timestamp

**Relasi:**
- `cabang_id` → `cabang(id)` ON DELETE RESTRICT

---

### 6. `riwayat_login_kasir`

Log history login kasir untuk audit.

```sql
CREATE TABLE riwayat_login_kasir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT(11) NOT NULL,
    nama_kasir VARCHAR(100) NOT NULL,
    login_at DATETIME NOT NULL,
    logout_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE RESTRICT,
    KEY idx_cabang (cabang_id),
    KEY idx_login (login_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- Sama dengan `session_kasir`
- Digunakan untuk tracking history login

---

### 7. `stok_cabang`

Level stok produk per cabang.

```sql
CREATE TABLE stok_cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT(11) NOT NULL,
    cabang_id INT(11) NOT NULL,
    jumlah INT(11) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_produk_cabang (produk_id, cabang_id),
    KEY idx_produk (produk_id),
    KEY idx_cabang (cabang_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `produk_id` — Foreign key ke `produk`
- `cabang_id` — Foreign key ke `cabang`
- `jumlah` — Jumlah stok di cabang
- `created_at`, `updated_at` — Timestamp

**Constraint:**
- UNIQUE pada kombinasi `(produk_id, cabang_id)`
- Satu produk hanya punya 1 record per cabang

**Relasi:**
- `produk_id` → `produk(id)` ON DELETE RESTRICT
- `cabang_id` → `cabang(id)` ON DELETE RESTRICT

---

### 8. `stok_masuk`

Transaksi stok masuk dari supplier ke gudang.

```sql
CREATE TABLE stok_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT(11) NOT NULL,
    jumlah INT(11) NOT NULL DEFAULT 0,
    tanggal DATETIME NOT NULL,
    batch_id VARCHAR(50) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT,
    KEY idx_produk (produk_id),
    KEY idx_tanggal (tanggal),
    KEY idx_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `produk_id` — Foreign key ke `produk`
- `jumlah` — Jumlah stok masuk
- `tanggal` — Tanggal transaksi
- `batch_id` — Group ID untuk batch entry
- `keterangan` — Catatan tambahan
- `created_at`, `updated_at` — Timestamp

**Relasi:**
- `produk_id` → `produk(id)` ON DELETE RESTRICT

---

### 9. `stok_keluar`

Transaksi stok keluar (rusak, transfer, dll).

```sql
CREATE TABLE stok_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT(11) NOT NULL,
    cabang_id INT(11) DEFAULT NULL,
    jumlah INT(11) NOT NULL DEFAULT 0,
    jenis ENUM('rusak', 'transfer') NOT NULL,
    tanggal DATETIME NOT NULL,
    batch_id VARCHAR(50) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT,
    KEY idx_produk (produk_id),
    KEY idx_jenis (jenis),
    KEY idx_tanggal (tanggal),
    KEY idx_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `produk_id` — Foreign key ke `produk`
- `cabang_id` — Target cabang (untuk transfer)
- `jumlah` — Jumlah stok keluar
- `jenis` — Jenis: `rusak` atau `transfer`
- `tanggal` — Tanggal transaksi
- `batch_id` — Group ID untuk batch entry
- `keterangan` — Catatan tambahan
- `created_at`, `updated_at` — Timestamp

**Jenis Stok Keluar:**
- `rusak` — Stok rusak/expired (tidak masuk ke cabang)
- `transfer` — Transfer stok dari gudang ke cabang

**Relasi:**
- `produk_id` → `produk(id)` ON DELETE RESTRICT

---

### 10. `stock_opname`

Catatan stock opname (pemeriksaan fisik stok).

```sql
CREATE TABLE stock_opname (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT(11) NOT NULL,
    stok_sistem INT(11) NOT NULL DEFAULT 0,
    stok_fisik INT(11) NOT NULL DEFAULT 0,
    selisih INT(11) NOT NULL DEFAULT 0,
    status ENUM('HILANG', 'KETEMU') NOT NULL,
    keterangan TEXT DEFAULT NULL,
    is_cancelled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT,
    KEY idx_produk (produk_id),
    KEY idx_status (status),
    KEY idx_cancelled (is_cancelled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `produk_id` — Foreign key ke `produk`
- `stok_sistem` — Stok di sistem (sebelum opname)
- `stok_fisik` — Stok hasil penghitungan fisik
- `selisih` — Selisih (stok_fisik - stok_sistem)
- `status` — Status: `HILANG` (negatif) atau `KETEMU` (positif)
- `keterangan` — Catatan
- `is_cancelled` — Flag pembatalan (0=aktif, 1=dibatalkan)
- `created_at`, `updated_at` — Timestamp

**Status:**
- `HILANG` — Stok fisik < stok sistem (ada barang hilang)
- `KETEMU` — Stok fisik > stok sistem (ada barang ketemu)

**Relasi:**
- `produk_id` → `produk(id)` ON DELETE RESTRICT

---

### 11. `transaksi_header`

Header invoice transaksi penjualan/pembelian.

```sql
CREATE TABLE transaksi_header (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT(11) NOT NULL,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    tipe ENUM('pembeli', 'penjual') NOT NULL DEFAULT 'pembeli',
    nama_pelanggan VARCHAR(100) DEFAULT NULL,
    total INT(11) NOT NULL DEFAULT 0,
    bayar INT(11) NOT NULL DEFAULT 0,
    kembalian INT(11) NOT NULL DEFAULT 0,
    tanggal DATETIME NOT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (session_id) REFERENCES session_kasir(id) ON DELETE RESTRICT,
    KEY idx_session (session_id),
    KEY idx_invoice (invoice_no),
    KEY idx_tipe (tipe),
    KEY idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `session_id` — Foreign key ke `session_kasir`
- `invoice_no` — Nomor invoice (format: INV-YYYYMMDD-XXXX)
- `tipe` — Tipe transaksi: `pembeli` atau `penjual`
- `nama_pelanggan` — Nama pembeli/penjual
- `total` — Total harga
- `bayar` — Jumlah bayar
- `kembalian` — Kembalian (bayar - total)
- `tanggal` — Tanggal transaksi
- `created_at`, `updated_at` — Timestamp

**Tipe Transaksi:**
- `pembeli` — Penjualan ke customer (kasir)
- `penjual` — Pembelian dari supplier/reseller

**Relasi:**
- `session_id` → `session_kasir(id)` ON DELETE RESTRICT

---

### 12. `transaksi_detail`

Detail item per transaksi.

```sql
CREATE TABLE transaksi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    header_id INT(11) NOT NULL,
    produk_id INT(11) NOT NULL,
    qty INT(11) NOT NULL DEFAULT 0,
    harga INT(11) NOT NULL DEFAULT 0,
    subtotal INT(11) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (header_id) REFERENCES transaksi_header(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT,
    KEY idx_header (header_id),
    KEY idx_produk (produk_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `header_id` — Foreign key ke `transaksi_header`
- `produk_id` — Foreign key ke `produk`
- `qty` — Jumlah item
- `harga` — Harga satuan
- `subtotal` — Total (qty × harga)
- `created_at`, `updated_at` — Timestamp

**Relasi:**
- `header_id` → `transaksi_header(id)` ON DELETE CASCADE
- `produk_id` → `produk(id)` ON DELETE RESTRICT

**Cascade Delete:**
- Jika header dihapus, semua detail ikut terhapus

---

### 13. `migrations`

Tracking migrasi database (system table).

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT(11) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Kolom:**
- `id` — Primary key
- `migration` — Nama file migrasi
- `batch` — Batch number (untuk rollback)
- `executed_at` — Timestamp eksekusi

---

## Relasi Antar Tabel

### Primary Relations

```
cabang (1) ──< (N) session_kasir
cabang (1) ──< (N) riwayat_login_kasir
cabang (1) ──< (N) stok_cabang (N) >── (1) produk

produk (1) ──< (N) stok_masuk
produk (1) ──< (N) stok_keluar
produk (1) ──< (N) stock_opname
produk (1) ──< (N) transaksi_detail

session_kasir (1) ──< (N) transaksi_header (1) ──< (N) transaksi_detail
```

### Foreign Key Constraints

| Tabel                 | Foreign Key   | References         | On Delete |
|-----------------------|---------------|--------------------|-----------|
| `session_kasir`       | `cabang_id`   | `cabang(id)`       | RESTRICT  |
| `riwayat_login_kasir` | `cabang_id`   | `cabang(id)`       | RESTRICT  |
| `stok_cabang`         | `produk_id`   | `produk(id)`       | RESTRICT  |
| `stok_cabang`         | `cabang_id`   | `cabang(id)`       | RESTRICT  |
| `stok_masuk`          | `produk_id`   | `produk(id)`       | RESTRICT  |
| `stok_keluar`         | `produk_id`   | `produk(id)`       | RESTRICT  |
| `stock_opname`        | `produk_id`   | `produk(id)`       | RESTRICT  |
| `transaksi_header`    | `session_id`  | `session_kasir(id)`| RESTRICT  |
| `transaksi_detail`    | `header_id`   | `transaksi_header(id)` | CASCADE |
| `transaksi_detail`    | `produk_id`   | `produk(id)`       | RESTRICT  |

**Catatan:**
- **RESTRICT**: Tidak bisa delete parent jika ada child
- **CASCADE**: Delete parent otomatis delete child

---

## Trigger dan Stored Procedure

### Trigger: `sync_stok_gudang_after_stock_opname`

Trigger ini **otomatis mengurangi atau menambah** `stok_gudang` di tabel `produk` berdasarkan hasil stock opname.

```sql
CREATE TRIGGER sync_stok_gudang_after_stock_opname
AFTER INSERT ON stock_opname
FOR EACH ROW
BEGIN
    UPDATE produk
    SET stok_gudang = stok_gudang + NEW.selisih
    WHERE id = NEW.produk_id;
END;
```

**Cara Kerja:**
- Insert record baru ke `stock_opname`
- Trigger otomatis update `produk.stok_gudang` dengan `+ selisih`
- Jika selisih negatif (HILANG), stok berkurang
- Jika selisih positif (KETEMU), stok bertambah

**Contoh:**
```
Produk A stok sistem: 100
Stock opname fisik: 95
Selisih: -5 (HILANG)
→ Trigger update: stok_gudang = 100 + (-5) = 95
```

---

## Indexes

Tabel-tabel utama sudah dilengkapi dengan index untuk optimasi query:

- **Primary Keys**: Semua tabel
- **Foreign Keys**: Auto-indexed
- **Custom Indexes**:
  - `produk.kode_produk` (unique)
  - `produk.status`
  - `transaksi_header.invoice_no` (unique)
  - `transaksi_header.tanggal`
  - `stok_masuk.tanggal`
  - `stok_keluar.tanggal`
  - `pengeluaran.tanggal`
  - `session_kasir.login_at`

---

## Query Examples

### Stok Produk Per Cabang

```sql
SELECT
    p.kode_produk,
    p.nama_produk,
    c.nama_cabang AS cabang,
    sc.stok AS stok
FROM stok_cabang sc
JOIN produk p ON sc.produk_id = p.id
JOIN cabang c ON sc.cabang_id = c.id
WHERE p.status = 'active'
ORDER BY c.nama_cabang, p.nama_produk;
```

### Laporan Penjualan Harian

```sql
SELECT
    th.no_invoice,
    th.created_at,
    th.total_harga,
    sk.nama_kasir,
    c.nama_cabang AS cabang
FROM transaksi_header th
JOIN session_kasir sk ON th.session_kasir_id = sk.id
JOIN cabang c ON sk.cabang_id = c.id
WHERE DATE(th.created_at) = CURDATE()
ORDER BY th.created_at DESC;
```

### Total Stok Rusak Per Produk

```sql
SELECT
    p.nama_produk,
    SUM(sk.jumlah) AS total_rusak
FROM stok_keluar sk
JOIN produk p ON sk.produk_id = p.id
WHERE sk.kondisi = 'rusak'
GROUP BY p.id, p.nama_produk
ORDER BY total_rusak DESC;
```

### Produk Aktif dengan Stok Gudang

```sql
SELECT
    kode_produk,
    nama_produk,
    harga_beli,
    stok_gudang
FROM produk
WHERE status = 'active'
AND stok_gudang > 0
ORDER BY nama_produk;
```

---

## Best Practices

### 1. Soft Delete Produk

**JANGAN:**
```sql
DELETE FROM produk WHERE id = 1;
```

**LAKUKAN:**
```sql
UPDATE produk
SET status = 'deleted',
    deleted_at = NOW()
WHERE id = 1;
```

### 2. Query Produk Aktif

Selalu filter `status = 'active'`:

```sql
SELECT * FROM produk WHERE status = 'active';
```

### 3. Transaction untuk Multiple Insert

Gunakan transaction untuk insert batch:

```sql
START TRANSACTION;

INSERT INTO stok_masuk (produk_id, jumlah, created_at, batch_id) VALUES (1, 10, NOW(), 'BATCH001');
UPDATE produk SET stok_gudang = stok_gudang + 10 WHERE id = 1;

COMMIT;
```

### 4. Index Usage

Pastikan WHERE clause menggunakan indexed column:

```sql
-- GOOD (menggunakan index)
SELECT * FROM transaksi_header WHERE created_at >= '2026-01-01';

-- BAD (tidak menggunakan index)
SELECT * FROM transaksi_header WHERE DATE_FORMAT(created_at, '%Y-%m') = '2026-01';
```

---

## Backup & Maintenance

### Backup Database

```bash
# Full backup
mysqldump -uroot -p kasir_toko > backup.sql

# Schema only
mysqldump -uroot -p --no-data kasir_toko > schema.sql

# Data only
mysqldump -uroot -p --no-create-info kasir_toko > data.sql
```

### Restore Database

```bash
mysql -uroot -p kasir_toko < backup.sql
```

### Optimize Tables

```sql
OPTIMIZE TABLE produk, stok_cabang, transaksi_header, transaksi_detail;
```

### Check Table Integrity

```sql
CHECK TABLE produk, stok_cabang, transaksi_header, transaksi_detail;
```

---

**Last Updated:** 2026-02-28
