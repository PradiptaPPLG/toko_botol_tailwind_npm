# Docker Setup Guide

## Prerequisites
- Docker Desktop installed and running
- Docker Compose

## Quick Start

1. **Clone the repository and navigate to project directory**
   ```bash
   cd toko_botol_tailwind_npm
   ```

2. **Ensure .env file exists** (already configured)
   ```bash
   # The .env file is already set up with:
   # - DB_HOST=db
   # - DB_USER=root
   # - DB_PASS=secret
   # - DB_NAME=kasir_toko
   ```

3. **Build and start containers**
   ```bash
   docker-compose up -d
   ```

   This will:
   - Build the PHP container with PHP 8.2-FPM and Node.js
   - Start MariaDB database
   - Start Nginx web server
   - Install npm dependencies
   - Build TailwindCSS automatically
   - Run database migrations and seeders

4. **Access the application**
   ```
   http://localhost:8080
   ```

## Container Details

### Services
- **nginx**: Web server on port 8080
- **php**: PHP-FPM 8.2 with Node.js/npm for TailwindCSS builds
- **db**: MariaDB database on port 3307 (external)

### Volumes
- `db_data`: Persistent database storage
- Project directory mounted to `/var/www/html` in containers

## Useful Commands

### View logs
```bash
docker-compose logs -f
docker-compose logs -f php    # PHP container logs only
docker-compose logs -f nginx  # Nginx logs only
docker-compose logs -f db     # Database logs only
```

### Stop containers
```bash
docker-compose down
```

### Rebuild containers (after Dockerfile changes)
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Reset database (fresh migrations)
```bash
docker-compose exec php php migrate fresh --seed
```

### Build TailwindCSS manually
```bash
docker-compose exec php npm run build
```

### Watch TailwindCSS for development
```bash
docker-compose exec php npm run watch
```

### Access PHP container shell
```bash
docker-compose exec php sh
```

### Access MariaDB
```bash
docker-compose exec db mysql -uroot -psecret kasir_toko
```

## Troubleshooting

### Port already in use
If ports 8080 or 3307 are already in use, modify `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Change 8080 to 8081
  # or
  - "3308:3306"  # Change 3307 to 3308
```

### CSS not updating
Rebuild TailwindCSS:
```bash
docker-compose exec php npm run build
```

### Database connection issues
Check if MariaDB is healthy:
```bash
docker-compose ps
```

Restart services:
```bash
docker-compose restart
```

### Clear all and start fresh
```bash
docker-compose down -v  # Remove volumes
docker-compose build --no-cache
docker-compose up -d
```

## Development Workflow

1. **Edit PHP files** - Changes are reflected immediately (mounted volume)
2. **Edit CSS in input.css** - Run `docker-compose exec php npm run build` or use watch mode
3. **Database changes** - Run migrations manually if needed

## Production Deployment

For production, consider:
1. Change database password in `.env` and `docker-compose.yml`
2. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
3. Use proper secrets management
4. Configure proper backups for `db_data` volume
5. Use reverse proxy (Traefik, Nginx Proxy Manager) for SSL/TLS

## Architecture

```
┌─────────────────────────────────────────┐
│          Browser (localhost:8080)        │
└───────────────────┬─────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Nginx Container (Port 80)        │
│  - Serves static files                   │
│  - Proxies PHP to PHP-FPM                │
└───────────────────┬─────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│        PHP-FPM Container (Port 9000)     │
│  - PHP 8.2 with mysqli extension         │
│  - Node.js + npm for TailwindCSS         │
│  - Auto-run migrations on first start    │
└───────────────────┬─────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│      MariaDB Container (Port 3306)       │
│  - Database: kasir_toko                  │
│  - Persistent storage: db_data volume    │
└─────────────────────────────────────────┘
```
