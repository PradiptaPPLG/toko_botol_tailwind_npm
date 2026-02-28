# 🏪 Sistem POS Toko Botol

Aplikasi Point of Sale (POS) modern untuk toko botol dengan manajemen stok multi-cabang, sistem kasir, dan laporan lengkap. Dibangun dengan PHP native, TailwindCSS, dan Docker.

## ✨ Fitur Utama

- 🏪 **Multi-Cabang** — Kelola stok dan transaksi untuk beberapa cabang
- 💰 **Sistem Kasir** — Interface kasir cepat dengan mode pembeli/penjual
- 📦 **Manajemen Stok** — Stok masuk, keluar, transfer, dan opname
- 📊 **Laporan Lengkap** — Laporan penjualan, pembelian, dan pengeluaran
- 🔐 **Multi-Role** — Admin, Gudang, dan Kasir
- 🐳 **Docker Ready** — Deploy dengan satu perintah
- 🎨 **Modern UI** — TailwindCSS dengan desain responsif

## 🚀 Instalasi Cepat (Docker)

### Prasyarat
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) terinstal dan berjalan

### Langkah Instalasi

```bash
# Clone repository
git clone <repository-url>
cd toko_botol_tailwind_npm

# Pastikan Docker Desktop berjalan, lalu jalankan:
docker-compose up -d
```

**Selesai!** 🎉 Aplikasi berjalan di **http://localhost:8080**

### Login Default

| Role   | Username | Password  |
|--------|---------|-----------|
| Admin  | admin   | @admin123 |

## 📚 Dokumentasi

- **[Dokumentasi Lengkap (Bahasa Indonesia)](docs/INSTALASI.md)** — Panduan instalasi lengkap dengan Docker dan manual
- **[Docker Guide (English)](docs/DOCKER.md)** — Docker setup, commands, and troubleshooting
- **[Database Schema](docs/DATABASE.md)** — Skema database dan relasi tabel
- **[API Documentation](docs/API.md)** — Endpoint API dan contoh penggunaan

## 🛠️ Tech Stack

- **Backend:** PHP 8.2 (Native)
- **Database:** MariaDB 10.x / MySQL 8.0+
- **Frontend:** TailwindCSS v4, Vanilla JavaScript
- **Container:** Docker + Docker Compose
- **Web Server:** Nginx

## 📁 Struktur Proyek

```
toko_botol_tailwind_npm/
├── docker/                    # Docker configuration files
│   ├── nginx/                 # Nginx config
│   └── php/                   # PHP entrypoint script
├── src/                       # Application source code
│   ├── includes/              # Core files (auth, config, database)
│   ├── modules/               # Feature modules
│   │   ├── admin/             # Admin module
│   │   ├── gudang/            # Warehouse module
│   │   └── kasir/             # Cashier module
│   └── database/              # Migrations & seeders
│       ├── migrations/        # Database migrations
│       └── seeders/           # Database seeders
├── dist/                      # Compiled CSS output
├── input.css                  # TailwindCSS source
├── docker-compose.yml         # Docker services configuration
└── Dockerfile                 # PHP container image
```

## 🔧 Perintah Berguna

```bash
# Lihat logs container
docker-compose logs -f

# Stop aplikasi
docker-compose down

# Rebuild container
docker-compose build --no-cache
docker-compose up -d

# Reset database
docker-compose exec php php migrate fresh --seed

# Build TailwindCSS
docker-compose exec php npm run build

# Watch TailwindCSS (development)
docker-compose exec php npm run watch

# Akses shell container
docker-compose exec php sh

# Akses database
docker-compose exec db mysql -uroot -psecret kasir_toko
```

## 🌐 Akses Aplikasi

| Service    | URL                        |
|------------|----------------------------|
| Web App    | http://localhost:8080      |
| Database   | localhost:3307             |

## 🏗️ Development

### Tanpa Docker (Manual)

Jika ingin development tanpa Docker:

1. Install PHP 8.2+, MariaDB/MySQL, Node.js 18+
2. Copy `.env.example` ke `.env` dan sesuaikan konfigurasi
3. Jalankan:
   ```bash
   npm install
   npm run build
   php migrate fresh --seed
   ```
4. Jalankan dengan Laragon atau web server lainnya

Lihat [Dokumentasi Lengkap](docs/INSTALASI.md) untuk detail instalasi manual.

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan buat issue atau pull request.

## 🆘 Bantuan

Jika menemukan masalah:
1. Cek [Docker Guide](docs/DOCKER.md) untuk troubleshooting
2. Cek [Dokumentasi Lengkap](docs/INSTALASI.md)
3. Buat issue di repository ini

---

**Made with ❤️ for Indonesian Retail Stores**
