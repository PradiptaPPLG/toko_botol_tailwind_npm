# 📖 Dokumentasi Lengkap — Sistem POS Toko Botol

Panduan lengkap instalasi dan penggunaan Sistem POS Toko Botol menggunakan Docker atau instalasi manual.

## 📋 Daftar Isi

1. [Kebutuhan Sistem](#kebutuhan-sistem)
2. [Instalasi dengan Docker (Direkomendasikan)](#instalasi-dengan-docker-direkomendasikan)
3. [Instalasi Manual (Tanpa Docker)](#instalasi-manual-tanpa-docker)
4. [Konfigurasi Database](#konfigurasi-database)
5. [Migrasi Database](#migrasi-database)
6. [Seeder Data](#seeder-data)
7. [Penggunaan Aplikasi](#penggunaan-aplikasi)
8. [Troubleshooting](#troubleshooting)

---

## Kebutuhan Sistem

### Instalasi dengan Docker

| Kebutuhan       | Versi         |
| --------------- | ------------- |
| Docker Desktop  | Latest        |
| Docker Compose  | v2.0+         |
| RAM             | Minimal 4GB   |
| Disk Space      | Minimal 2GB   |

### Instalasi Manual

| Kebutuhan          | Versi         |
| ------------------ | ------------- |
| PHP                | 8.2+          |
| MariaDB / MySQL    | 10.4+ / 8.0+  |
| Node.js            | 18+           |
| npm                | 9+            |
| Laragon (opsional) | Latest        |

---

## Instalasi dengan Docker (Direkomendasikan)

Instalasi dengan Docker adalah cara tercepat dan paling mudah untuk menjalankan aplikasi ini.

### Langkah 1: Persiapan

1. **Install Docker Desktop**
   - Download dari [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/)
   - Install dan pastikan Docker Desktop berjalan
   - Verifikasi dengan membuka Terminal/CMD:
     ```bash
     docker --version
     docker-compose --version
     ```

2. **Clone atau Download Project**
   ```bash
   git clone <repository-url>
   cd toko_botol_tailwind_npm
   ```

### Langkah 2: Konfigurasi Environment

File `.env` sudah dikonfigurasi dengan default settings untuk Docker. Anda bisa menggunakannya langsung atau mengubah sesuai kebutuhan:

```bash
# File .env (sudah dikonfigurasi)
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST=db
DB_PORT=3306
DB_NAME=kasir_toko
DB_USER=root
DB_PASS=secret

TIMEZONE=Asia/Jakarta
```

### Langkah 3: Jalankan Aplikasi

```bash
# Build dan jalankan semua container
docker-compose up -d
```

Perintah ini akan:
- ✅ Membuild Docker image untuk PHP container
- ✅ Download image Nginx dan MariaDB
- ✅ Membuat network untuk komunikasi antar container
- ✅ Menjalankan semua service (nginx, php, mariadb)
- ✅ Menjalankan migrasi database otomatis
- ✅ Menginstal npm dependencies
- ✅ Build TailwindCSS otomatis
- ✅ Menjalankan seeder data otomatis

**Proses ini membutuhkan waktu 2-5 menit pada run pertama.**

### Langkah 4: Verifikasi

1. **Cek status container**
   ```bash
   docker-compose ps
   ```

   Pastikan semua container dalam status "Up":
   ```
   NAME             STATE
   toko_nginx       Up
   toko_php         Up
   toko_mariadb     Up (healthy)
   ```

2. **Cek logs jika ada masalah**
   ```bash
   docker-compose logs -f php
   ```

3. **Akses aplikasi**
   - Buka browser: **http://localhost:8080**
   - Login dengan kredensial default:
     - Username: `admin`
     - Password: `admin`

### Langkah 5: Perintah Docker Berguna

```bash
# Lihat logs semua container
docker-compose logs -f

# Lihat logs PHP container saja
docker-compose logs -f php

# Stop aplikasi
docker-compose down

# Stop dan hapus volume (data database akan hilang)
docker-compose down -v

# Restart container
docker-compose restart

# Rebuild container (setelah ubah Dockerfile)
docker-compose build --no-cache
docker-compose up -d

# Masuk ke shell PHP container
docker-compose exec php sh

# Jalankan perintah di dalam container
docker-compose exec php php migrate status
docker-compose exec php npm run build

# Akses database
docker-compose exec db mysql -uroot -psecret kasir_toko

# Reset database (fresh migration + seed)
docker-compose exec php php migrate fresh --seed
```

---

## Instalasi Manual (Tanpa Docker)

### Langkah 1: Install Dependencies

1. **Install PHP 8.2+**
   - Windows: Gunakan [Laragon](https://laragon.org/) atau XAMPP
   - Linux: `sudo apt install php8.2 php8.2-mysqli php8.2-mbstring`
   - MacOS: `brew install php@8.2`

2. **Install MariaDB/MySQL**
   - Windows: Sudah include di Laragon/XAMPP
   - Linux: `sudo apt install mariadb-server`
   - MacOS: `brew install mariadb`

3. **Install Node.js & npm**
   - Download dari [nodejs.org](https://nodejs.org/)
   - Verifikasi: `node --version && npm --version`

### Langkah 2: Setup Project

1. **Clone project**
   ```bash
   git clone <repository-url>
   cd toko_botol_tailwind_npm
   ```

2. **Install npm dependencies**
   ```bash
   npm install
   ```

3. **Build TailwindCSS**
   ```bash
   npm run build
   ```

### Langkah 3: Setup Database

1. **Buat database**
   ```sql
   CREATE DATABASE kasir_toko CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Copy dan edit file .env**
   ```bash
   cp .env.example .env
   ```

   Edit `.env` sesuai konfigurasi lokal Anda:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=kasir_toko
   DB_USER=root
   DB_PASS=your_password
   ```

### Langkah 4: Jalankan Migrasi

```bash
# Jalankan migrasi dan seeder
php migrate fresh --seed
```

### Langkah 5: Jalankan Web Server

**Dengan Laragon:**
1. Letakkan project di folder `C:\laragon\www\`
2. Start Laragon
3. Akses via browser: `http://toko_botol_tailwind_npm.test` atau `http://localhost/toko_botol_tailwind_npm`

**Dengan PHP Built-in Server:**
```bash
php -S localhost:8000
```
Akses via browser: `http://localhost:8000`

**Dengan Apache/Nginx:**
- Configure virtual host yang point ke root directory project
- Pastikan document root mengarah ke folder project

---

## Konfigurasi Database

### Docker
Konfigurasi database sudah otomatis melalui file `.env` dan akan di-generate oleh entrypoint script.

### Manual
Edit file `src/includes/config.php`:

```php
<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$host = 'localhost';
$user = 'root';
$pass = 'your_password';
$dbname = 'kasir_toko';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$root = '/';
$base_url = 'http://localhost' . $root;
```

---

## Migrasi Database

Sistem migrasi terinspirasi dari Laravel. Setiap migrasi adalah kelas PHP di folder `src/database/migrations/`.

### Perintah Migrasi

```bash
# Jalankan migrasi yang belum dieksekusi
php migrate

# Lihat status semua migrasi
php migrate status

# Reset database dan jalankan ulang semua migrasi
php migrate fresh

# Reset + jalankan seeder
php migrate fresh --seed

# Rollback batch terakhir
php migrate rollback

# Jalankan hanya seeder
php migrate db:seed
```

### Contoh Membuat Migrasi Baru

Buat file baru di `src/database/migrations/` dengan format:
`YYYY_MM_DD_NNNNNN_nama_deskriptif.php`

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
            $s->text('keterangan');
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

| Method                                           | SQL Output                                     |
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
| `->default($nilai)`                              | Set nilai DEFAULT                              |
| `->tinyint()`                                    | Ubah ke TINYINT(1)                             |
| `$s->unique('kolom')`                            | `UNIQUE KEY`                                   |
| `$s->index('kolom')`                             | `KEY` index                                    |
| `$s->foreign('kolom', 'tabel')`                  | `FOREIGN KEY ON DELETE RESTRICT`               |
| `$s->foreign('kolom', 'tabel', 'id', 'CASCADE')` | Dengan aksi `ON DELETE` kustom                 |

---

## Seeder Data

Seeder digunakan untuk mengisi data awal database. File seeder ada di `src/database/seeders/`.

### Seeder Bawaan

| Seeder             | Tabel         | Data                            |
| ------------------ | ------------- | ------------------------------- |
| `AdminSeeder`      | `admin`       | 1 akun admin default            |
| `CabangSeeder`     | `cabang`      | 3 cabang: Barat, Pusat, Timur   |
| `ProdukSeeder`     | `produk`      | 3 produk contoh: BTL001–BTL003  |
| `StokCabangSeeder` | `stok_cabang` | Stok awal per produk per cabang |

### Login Default

Setelah menjalankan seeder:

| Role  | Username | Password |
| ----- | -------- | -------- |
| Admin | admin    | admin    |

### Membuat Seeder Baru

Buat file di `src/database/seeders/`:

```php
<?php

class ContohSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel terlebih dahulu
        $this->truncate('contoh');

        // Insert banyak data sekaligus
        $this->insertMany('contoh', [
            ['nama' => 'Data A', 'nilai' => 10],
            ['nama' => 'Data B', 'nilai' => 20],
            ['nama' => 'Data C', 'nilai' => 30],
        ]);
    }
}
```

Tambahkan ke `DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call('ContohSeeder');
}
```

---

## Penggunaan Aplikasi

### Akses Aplikasi

| Environment | URL                               |
| ----------- | --------------------------------- |
| Docker      | http://localhost:8080             |
| Manual      | http://localhost atau sesuai vhost|

### Modul Aplikasi

#### 1. Dashboard
- Ringkasan penjualan hari ini
- Stok tersedia
- Grafik kerugian (stok rusak)

#### 2. Admin Module (`/src/modules/admin/`)

**Kelola Produk** (`tambah_stok.php`)
- Tambah produk baru
- Edit informasi produk
- Soft delete produk
- Lihat produk yang dihapus

**Info Cabang** (`info_cabang.php`)
- Lihat detail semua cabang
- Monitor stok per cabang
- Transfer stok antar cabang

**Laporan Penjualan** (`laporan_penjualan.php`)
- Filter berdasarkan tanggal dan cabang
- Export laporan
- Detail transaksi

**Laporan Pembelian** (`laporan_pembelian.php`)
- Transaksi tipe "penjual" (supplier)
- Rekap pembelian per periode

#### 3. Gudang Module (`/src/modules/gudang/`)

**Dashboard Gudang** (`index.php`)
- Overview stok gudang
- Quick actions untuk operasi stok

**Stok Masuk** (`stok_masuk.php`)
- Catat stok masuk dari supplier
- Batch entry untuk multiple produk

**Stok Transfer** (`stok_transfer.php`)
- Transfer stok ke cabang
- Otomatis update stok cabang

**Stok Rusak** (`stok_rusak.php`)
- Catat stok rusak/expired
- Kurangi stok otomatis

**Stok Opname** (`stok_opname.php`)
- Cek fisik stok
- Deteksi selisih (hilang/ketemu)
- Auto-adjust stok

**Pengeluaran** (`pengeluaran.php`)
- Catat pengeluaran operasional
- Kategori pengeluaran

#### 4. Kasir Module (`/src/modules/kasir/`)

**Interface Kasir** (`index.php`)
- Mode Pembeli: Jual ke customer
- Mode Penjual: Beli dari supplier
- Scan/pilih produk
- Hitung otomatis total
- Print struk

---

## Troubleshooting

### Docker Issues

**Error: Port 8080 already in use**
```bash
# Edit docker-compose.yml, ubah port:
ports:
  - "8081:80"  # Ganti 8080 dengan 8081
```

**Error: Docker daemon not running**
```bash
# Windows/Mac: Buka Docker Desktop
# Linux:
sudo systemctl start docker
```

**Error: Container tidak bisa connect ke database**
```bash
# Cek status container
docker-compose ps

# Cek logs
docker-compose logs -f db

# Restart services
docker-compose restart
```

**CSS tidak ter-compile**
```bash
# Build manual
docker-compose exec php npm run build

# Atau watch mode untuk development
docker-compose exec php npm run watch
```

**Database tidak ter-migrate**
```bash
# Jalankan manual
docker-compose exec php php migrate fresh --seed
```

### Manual Installation Issues

**Error: mysqli extension not found**
```bash
# Ubuntu/Debian
sudo apt install php8.2-mysqli

# Check php.ini, pastikan extension enabled:
extension=mysqli
```

**Error: TailwindCSS not building**
```bash
# Re-install dependencies
rm -rf node_modules package-lock.json
npm install
npm run build
```

**Error: Database connection failed**
- Cek apakah MariaDB/MySQL berjalan
- Verifikasi kredensial di `.env` atau `config.php`
- Test koneksi manual via mysql client

**Permission denied (Linux/Mac)**
```bash
# Set permission yang benar
chmod -R 755 src/
chmod -R 777 dist/
```

### General Issues

**Session tidak berfungsi**
- Pastikan `session_start()` dipanggil di `config.php`
- Cek permission folder session PHP

**Timezone salah**
- Edit `.env` atau `config.php`:
  ```php
  date_default_timezone_set('Asia/Jakarta');
  ```

---

## Performance Tips

### Development Mode

```bash
# Docker: Watch CSS changes
docker-compose exec php npm run watch

# Manual:
npm run watch
```

### Production Mode

1. **Build minified CSS**
   ```bash
   npm run build
   ```

2. **Disable Debug Mode**
   Edit `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Enable Opcache** (PHP config)
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   ```

4. **Optimize Autoload** (Composer)
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

---

## Backup & Restore

### Backup Database

```bash
# Docker
docker-compose exec db mysqldump -uroot -psecret kasir_toko > backup.sql

# Manual
mysqldump -uroot -p kasir_toko > backup.sql
```

### Restore Database

```bash
# Docker
docker-compose exec -T db mysql -uroot -psecret kasir_toko < backup.sql

# Manual
mysql -uroot -p kasir_toko < backup.sql
```

---

## Update Aplikasi

### Dengan Docker

```bash
# Pull perubahan terbaru
git pull origin main

# Rebuild container
docker-compose build --no-cache

# Restart
docker-compose down
docker-compose up -d

# Jalankan migrasi baru (jika ada)
docker-compose exec php php migrate
```

### Manual

```bash
# Pull perubahan
git pull origin main

# Update dependencies
npm install
composer install

# Rebuild CSS
npm run build

# Jalankan migrasi baru
php migrate
```

---

## FAQ

**Q: Bagaimana cara mengganti password admin?**
A: Login sebagai admin → Edit di database `admin` table atau buat fitur change password.

**Q: Bisa multi user admin?**
A: Ya, tambahkan record baru di tabel `admin` dengan password yang di-hash.

**Q: Bagaimana cara menambah cabang baru?**
A: Insert ke tabel `cabang` atau buat fitur management cabang.

**Q: Data hilang setelah restart container?**
A: Data tersimpan di Docker volume `db_data`. Hanya hilang jika volume dihapus dengan `docker-compose down -v`.

**Q: Bisa install di shared hosting?**
A: Ya, tapi tanpa Docker. Upload files, setup database, dan jalankan migrasi manual.

---

## Kontak & Support

Jika mengalami kesulitan:
1. Baca dokumentasi ini dengan teliti
2. Cek [Docker Guide](DOCKER.md) untuk troubleshooting Docker
3. Cek [Database Schema](DATABASE.md) untuk struktur database
4. Buat issue di repository dengan detail error

---

**Happy Coding! 🚀**
