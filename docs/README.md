# Dokumentasi Lengkap — POS Toko Botol

## Daftar Isi

1. [Kebutuhan Sistem](#kebutuhan-sistem)
2. [Instalasi](#instalasi)
3. [Konfigurasi Database](#konfigurasi-database)
4. [Migrasi Database](#migrasi-database)
5. [Seeder](#seeder)
6. [Skema Database](#skema-database)
7. [Modul Aplikasi](#modul-aplikasi)
8. [Konvensi Kode](#konvensi-kode)

---

## Kebutuhan Sistem

| Kebutuhan          | Versi         |
| ------------------ | ------------- |
| PHP                | 8.2+          |
| MariaDB / MySQL    | 10.4+ / 8.0+  |
| Node.js            | 18+           |
| Laragon (opsional) | Versi terbaru |
| Tailwind CSS       | v3 (via npm)  |

---

## Instalasi

### Instalasi Cepat

Jalankan salah satu perintah berikut sesuai kebutuhan:

```bash
# Development — CSS otomatis dikompilasi saat ada perubahan file
npm run setup:watch

# Production / Testing — CSS dikompilasi dan diminifikasi
npm run setup:build
```

Kedua perintah di atas secara otomatis akan:

1. Menginstall semua dependensi npm
2. Menyalin `includes/config.php.example` → `includes/config.php`
3. Menjalankan `php migrate fresh` (membuat seluruh tabel database)

> **Penting:** Setelah setup, buka `includes/config.php` dan sesuaikan kredensial database Anda.

### Perintah npm Lainnya

| Perintah                 | Keterangan                                 |
| ------------------------ | ------------------------------------------ |
| `npm run build`          | Kompilasi & minifikasi CSS Tailwind        |
| `npm run watch`          | Auto-kompilasi CSS saat file berubah       |

---

## Konfigurasi Database

Edit file `includes/config.php`:

```php
$host   = 'localhost';
$user   = 'root';
$pass   = 'password_anda';
$dbname = 'kasir_toko';
```

---

## Migrasi Database

Sistem migrasi terinspirasi dari Laravel. Setiap migrasi adalah kelas PHP di folder `database/migrations/`, dinamai dengan format `YYYY_MM_DD_NNNNNN_nama_migrasi.php`.

### Perintah CLI Migrasi

```bash
php migrate              # Jalankan migrasi yang belum dieksekusi
php migrate status       # Tampilkan status semua migrasi
php migrate fresh        # Hapus semua tabel & jalankan ulang (minta konfirmasi)
php migrate fresh --seed # Fresh migrate + seed data sekaligus
php migrate rollback     # Batalkan batch migrasi terakhir
php migrate db:seed      # Jalankan semua seeder
```

### Membuat Migrasi Baru

Buat file baru di `database/migrations/YYYY_MM_DD_NNNNNN_nama.php`:

```php
<?php

class CreateContohTable extends Migration
{
    public function up(): void
    {
        $this->createTable('contoh', function (Schema $s) {
            $s->id();
            $s->string('nama', 100);
            $s->integer('nilai')->default(0);
            $s->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropIfExists('contoh');
    }
}
```

### Referensi Schema Builder

| Method                                           | SQL yang Dihasilkan                            |
| ------------------------------------------------ | ---------------------------------------------- |
| `$s->id()`                                       | `INT AUTO_INCREMENT PRIMARY KEY`               |
| `$s->integer('kolom')`                           | `INT(11) NOT NULL DEFAULT 0`                   |
| `$s->integerNullable('kolom')`                   | `INT(11) DEFAULT NULL`                         |
| `$s->string('kolom', 100)`                       | `VARCHAR(100) NOT NULL`                        |
| `$s->stringNullable('kolom')`                    | `VARCHAR(255) DEFAULT NULL`                    |
| `$s->text('kolom')`                              | `TEXT NOT NULL`                                |
| `$s->textNullable('kolom')`                      | `TEXT DEFAULT NULL`                            |
| `$s->enum('kolom', ['a','b'])`                   | `ENUM('a','b') NOT NULL`                       |
| `$s->date('kolom')`                              | `DATE NOT NULL`                                |
| `$s->datetime('kolom')`                          | `DATETIME NOT NULL`                            |
| `$s->datetimeNullable('kolom')`                  | `DATETIME DEFAULT NULL`                        |
| `$s->timestamp('kolom')`                         | `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| `$s->timestamps()`                               | `created_at` + `updated_at` DATETIME           |
| `->default($nilai)`                              | Rantai: set nilai DEFAULT                      |
| `->tinyint()`                                    | Rantai: ubah ke TINYINT(1)                     |
| `$s->unique('kolom')`                            | `UNIQUE KEY`                                   |
| `$s->unique('nama_key', ['kol1','kol2'])`        | Composite unique key                           |
| `$s->index('kolom')`                             | `KEY` index                                    |
| `$s->index('kolom', 'nama')`                     | Named `KEY` index                              |
| `$s->foreign('kolom', 'tabel')`                  | `FOREIGN KEY ON DELETE RESTRICT`               |
| `$s->foreign('kolom', 'tabel', 'id', 'CASCADE')` | Dengan aksi `ON DELETE` kustom                 |

---

## Seeder

Seeder ada di folder `database/seeders/`. `DatabaseSeeder.php` adalah entry point utama.

### Seeder Bawaan

| Seeder             | Tabel         | Data                            |
| ------------------ | ------------- | ------------------------------- |
| `AdminSeeder`      | `admin`       | 1 akun admin default            |
| `CabangSeeder`     | `cabang`      | 3 cabang: Barat, Pusat, Timur   |
| `ProdukSeeder`     | `produk`      | 3 produk contoh: BTL001–BTL003  |
| `StokCabangSeeder` | `stok_cabang` | Stok awal per produk per cabang |

### Membuat Seeder Baru

```php
<?php

class ContohSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate('contoh');

        $this->insertMany('contoh', [
            ['nama' => 'Data A', 'nilai' => 10],
            ['nama' => 'Data B', 'nilai' => 20],
        ]);
    }
}
```

Lalu tambahkan `$this->call('ContohSeeder');` di dalam `DatabaseSeeder::run()`.

---

## Skema Database

### Daftar Tabel

| Tabel                 | Keterangan                                                  |
| --------------------- | ----------------------------------------------------------- |
| `admin`               | Akun pengguna admin                                         |
| `cabang`              | Data cabang toko                                            |
| `produk`              | Produk (dengan soft-delete: `status = 'active'\|'deleted'`) |
| `pengeluaran`         | Catatan pengeluaran toko                                    |
| `session_kasir`       | Sesi login kasir                                            |
| `riwayat_login_kasir` | Log riwayat login kasir                                     |
| `stok_cabang`         | Level stok produk per cabang                                |
| `stok_masuk`          | Catatan stok masuk (dari gudang)                            |
| `stok_keluar`         | Catatan stok keluar (rusak/transfer)                        |
| `stock_opname`        | Catatan opname stok fisik                                   |
| `transaksi_header`    | Invoice transaksi (satu per penjualan)                      |
| `transaksi_detail`    | Item transaksi (banyak per invoice)                         |
| `migrations`          | Riwayat migrasi (dikelola otomatis)                         |

### Relasi Utama

```
cabang ←── session_kasir
cabang ←── riwayat_login_kasir
cabang ←── transaksi_header
cabang ←── stok_cabang ──→ produk
produk ←── stok_masuk
produk ←── stok_keluar
produk ←── stock_opname
session_kasir ←── transaksi_header ──→ transaksi_detail ──→ produk
```

---

## Modul Aplikasi

### Admin (`modules/admin/`)

| File                         | Keterangan                              |
| ---------------------------- | --------------------------------------- |
| `tambah_stok.php`            | Tambah / edit / hapus produk            |
| `info_cabang.php`            | Lihat detail cabang dan stok per cabang |
| `laporan_penjualan.php`      | Laporan penjualan (tipe = `pembeli`)    |
| `laporan_pembelian.php`      | Laporan pembelian (tipe = `penjual`)    |
| `get_transaction_detail.php` | Endpoint AJAX: detail transaksi         |

### Gudang (`modules/gudang/`)

| File              | Keterangan                         |
| ----------------- | ---------------------------------- |
| `index.php`       | Dashboard gudang utama             |
| `stok_masuk.php`  | Catat stok masuk dari supplier     |
| `stok_keluar.php` | Catat stok keluar (rusak/transfer) |
| `stok_opname.php` | Opname stok fisik                  |
| `pengeluaran.php` | Catat pengeluaran toko             |

### Kasir (`modules/kasir/`)

| File        | Keterangan                                           |
| ----------- | ---------------------------------------------------- |
| `index.php` | Antarmuka kasir — jual produk (mode pembeli/penjual) |

---

## Konvensi Kode

### Tipe Transaksi (`tipe`)

- `pembeli` — penjualan ke pelanggan
- `penjual` — pembelian dari supplier (reseller)

### Format Nomor Invoice

`INV-YYYYMMDD-XXXX` — dibuat otomatis oleh fungsi `generate_invoice()` di `includes/functions.php`.

### Mata Uang

Semua harga disimpan sebagai **integer (IDR, tanpa desimal)**. Tampilkan menggunakan helper `rupiah($nominal)` yang memformat menjadi `Rp 1.000`.

### Soft Delete pada `produk`

Produk **tidak pernah dihapus permanen**. Gunakan `status = 'deleted'`:

```php
// Soft delete
execute("UPDATE produk SET status='deleted', deleted_at=NOW() WHERE id=$id");

// Query produk aktif saja
query("SELECT * FROM produk WHERE status = 'active'");
```

### Operasi Batch

`stok_masuk` dan `stok_keluar` mendukung kolom `batch_id` untuk mengelompokkan beberapa item dalam satu operasi.

### Alur Autentikasi

1. `login.php` — formulir login admin
2. `includes/auth.php` — penjaga sesi (include di awal setiap halaman yang dilindungi)
3. `logout.php` — hancurkan sesi
