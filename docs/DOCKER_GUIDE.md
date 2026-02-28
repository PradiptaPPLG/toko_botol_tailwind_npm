# 🐳 Panduan Docker — Aplikasi Kasir Toko Botol

## Daftar Isi

- [Arsitektur](#arsitektur)
- [Prasyarat](#prasyarat)
- [Setup Development](#setup-development)
- [Setup Production](#setup-production)
- [Environment Variables](#environment-variables)
- [Auto Backup Database](#auto-backup-database)
- [CI/CD — GitHub Actions](#cicd--github-actions)
- [Perintah Berguna](#perintah-berguna)
- [Troubleshooting](#troubleshooting)

---

## Arsitektur

Aplikasi terdiri dari 4 container:

| Container | Image | Fungsi |
|-----------|-------|--------|
| `toko_nginx` | `nginx:alpine` | Web server (port 80/8080) |
| `toko_php` | Custom (PHP 8.2 FPM Alpine) | Menjalankan aplikasi PHP |
| `toko_mariadb` | `mariadb:alpine` | Database MariaDB |
| `toko_backup` | Custom (Alpine + cron) | Auto backup DB setiap hari jam 00:00 |

**Dockerfile menggunakan multi-stage build:**
1. **Stage 1 (Node.js):** Build TailwindCSS → menghasilkan `dist/tailwind.css`
2. **Stage 2 (PHP-FPM):** Copy hasil build CSS + source code → image production tanpa Node.js

---

## Prasyarat

- [Docker](https://docs.docker.com/get-docker/) (versi 20+)
- [Docker Compose](https://docs.docker.com/compose/install/) (versi 2+)

---

## Setup Development

### 1. Clone repository

```bash
git clone https://github.com/PradiptaPPLG/toko_botol_tailwind_npm.git
cd toko_botol_tailwind_npm
```

### 2. Buat file `.env`

```bash
cp .env.example .env
```

Edit `.env` sesuai kebutuhan:

```env
DB_HOST=db
DB_PORT=3306
DB_NAME=kasir_toko
DB_USER=root
DB_PASS=secret
```

### 3. Jalankan semua container

```bash
docker compose up -d --build
```

### 4. Akses aplikasi

Buka browser → **http://localhost:8080**

> **Catatan:** Pada mode development, source code di-mount langsung dari folder lokal menggunakan volume bind mount. Perubahan file langsung terlihat tanpa rebuild.

### 5. Stop semua container

```bash
docker compose down
```

---

## Setup Production

Mode production menggunakan image yang sudah di-build dan di-push ke GitHub Container Registry (GHCR).

### 1. Siapkan file di server

Di server production, buat folder dan siapkan file berikut:

```bash
mkdir toko-botol && cd toko-botol
```

Buat file `docker-compose.prod.yml` dan `.env` (copy dari `.env.example`):

```bash
cp .env.example .env
nano .env
```

Pastikan `.env` memiliki konfigurasi berikut:

```env
DB_HOST=db
DB_NAME=kasir_toko
DB_USER=root
DB_PASS=password_kuat_anda

# Image dari GHCR
DOCKER_IMAGE=ghcr.io/pradiptapplg/toko_botol_tailwind_npm:latest
```

> **Semua konfigurasi ada di `.env`** — tidak perlu edit file compose.
> **Database tidak di-expose ke publik** — hanya bisa diakses dari container internal.

### 2. Login ke GHCR

```bash
echo "GITHUB_TOKEN" | docker login ghcr.io -u USERNAME --password-stdin
```

### 3. Pull dan jalankan

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

### 4. Update ke versi terbaru

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

> **Hemat bandwidth:** Server hanya perlu `docker pull` (~30MB) — tidak perlu clone repo, install Node.js, atau build CSS lagi.

---

## Environment Variables

| Variable | Default | Keterangan |
|----------|---------|------------|
| `DB_HOST` | `db` | Hostname database (nama service di compose) |
| `DB_PORT` | `3306` | Port database |
| `DB_NAME` | `kasir_toko` | Nama database |
| `DB_USER` | `root` | Username database |
| `DB_PASS` | `secret` | Password database |
| `FORCE_CONFIG` | `0` | Set `1` untuk regenerate `config.php` |
| `DOCKER_IMAGE` | `ghcr.io/pradiptapplg/toko_botol_tailwind_npm:latest` | Image PHP untuk production |

---

## Auto Backup Database

Backup otomatis berjalan setiap hari **jam 00:00 WIB** menggunakan container `toko_backup`.

### Cara kerja

- Menggunakan `mysqldump` + `gzip` untuk kompresi
- Backup disimpan di folder `./backups/` pada host
- Format nama file: `kasir_toko_2026-02-28_00-00-00.sql.gz`
- **Otomatis hapus** backup yang lebih dari **7 hari**

### Manual backup

```bash
docker exec toko_backup /usr/local/bin/backup.sh
```

### Cek log backup

```bash
docker exec toko_backup cat /var/log/backup.log
```

### Restore dari backup

```bash
# Ekstrak file backup
gunzip -k backups/kasir_toko_2026-02-28_00-00-00.sql.gz

# Restore ke database
docker exec -i toko_mariadb mysql -u root -psecret kasir_toko < backups/kasir_toko_2026-02-28_00-00-00.sql
```

---

## CI/CD — GitHub Actions

Workflow otomatis di `.github/workflows/docker-build.yml`:

### Alur kerja

```
Push ke main → GitHub Actions build image → Push ke GHCR → Server pull image terbaru
```

### Yang dilakukan workflow:

1. ✅ Checkout repository
2. ✅ Build multi-stage Dockerfile (Node build + PHP production)
3. ✅ Push image ke GHCR (menggunakan `GITHUB_TOKEN`, tanpa secret tambahan)
4. ✅ Cache Docker layer untuk build lebih cepat

### Tag image:

| Tag | Keterangan |
|-----|------------|
| `latest` | Selalu menunjuk ke build terbaru |
| `abc1234` | Tag berdasarkan commit SHA (untuk rollback) |

### Deploy di server setelah CI build:

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d --force-recreate
```

> **Tips:** Tambahkan webhook atau SSH deploy step di workflow untuk deploy otomatis ke server.

---

## Perintah Berguna

### Container Management

```bash
# Lihat status container
docker compose ps

# Lihat log semua container
docker compose logs -f

# Lihat log container tertentu
docker compose logs -f php

# Masuk ke container PHP
docker exec -it toko_php sh

# Masuk ke container MariaDB
docker exec -it toko_mariadb mysql -u root -psecret kasir_toko
```

### Database

```bash
# Jalankan migrasi manual
docker exec toko_php php migrate

# Cek status migrasi
docker exec toko_php php migrate status

# Fresh migration + seed (⚠ HAPUS SEMUA DATA)
docker exec -it toko_php sh -c 'echo "yes" | php migrate fresh --seed'
```

### Rebuild

```bash
# Rebuild image setelah perubahan Dockerfile
docker compose up -d --build

# Rebuild tanpa cache
docker compose build --no-cache
```

---

## Troubleshooting

### Container tidak bisa start

```bash
# Cek log error
docker compose logs php
docker compose logs db
```

### Database connection refused

- Pastikan container `db` sudah healthy: `docker compose ps`
- Cek environment variable `DB_HOST` di `.env` sesuai nama service (`db`)

### CSS tidak muncul / tampilan rusak

- Pastikan build TailwindCSS berhasil: cek file `dist/tailwind.css` di dalam container
  ```bash
  docker exec toko_php ls -la dist/
  ```
- Jika file tidak ada, rebuild image: `docker compose up -d --build`

### Backup tidak berjalan

```bash
# Cek apakah container backup running
docker compose ps backup

# Test manual
docker exec toko_backup /usr/local/bin/backup.sh

# Cek crontab
docker exec toko_backup crontab -l
```

### Reset total (⚠ HAPUS SEMUA DATA)

```bash
docker compose down -v
docker compose up -d --build
```
