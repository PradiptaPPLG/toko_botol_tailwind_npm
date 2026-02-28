#!/bin/sh
set -e

echo "🚀 Starting application setup..."

# Wait for MariaDB to be ready
echo "⏳ Waiting for MariaDB..."
while ! php -r "new mysqli('${DB_HOST:-mariadb}', '${DB_USER:-root}', '${DB_PASS:-secret}');" 2>/dev/null; do
    sleep 1
done
echo "✅ MariaDB is ready!"

# Generate config.php from environment variables
CONFIG_FILE="/var/www/html/includes/config.php"
if [ ! -f "$CONFIG_FILE" ] || [ "${FORCE_CONFIG:-0}" = "1" ]; then
    echo "📝 Generating config.php..."
    cat > "$CONFIG_FILE" <<PHPEOF
<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

\$host = '${DB_HOST:-mariadb}';
\$user = '${DB_USER:-root}';
\$pass = '${DB_PASS:-secret}';
\$dbname = '${DB_NAME:-kasir_toko}';

\$conn = new mysqli(\$host, \$user, \$pass, \$dbname);

if (\$conn->connect_error) {
    die("Koneksi gagal: " . \$conn->connect_error);
}

\$root = '/';
\$base_url = 'http://localhost' . \$root;
PHPEOF
    echo "✅ config.php generated!"
fi

# Install npm dependencies and build TailwindCSS if needed
if [ ! -d "/var/www/html/node_modules" ]; then
    echo "📦 Installing npm dependencies..."
    cd /var/www/html && npm install
fi

if [ ! -f "/var/www/html/dist/tailwind.css" ]; then
    echo "🎨 Building TailwindCSS..."
    cd /var/www/html && npm run build
fi

# Run migrations on first start
MIGRATION_FLAG="/var/www/html/.docker_migrated"
if [ ! -f "$MIGRATION_FLAG" ]; then
    echo "🗄️ Running migrations..."
    cd /var/www/html && echo "yes" | php migrate fresh --seed
    touch "$MIGRATION_FLAG"
    echo "✅ Migrations complete!"
fi

echo "🎉 Application ready!"

# Execute the main command (php-fpm)
exec "$@"
