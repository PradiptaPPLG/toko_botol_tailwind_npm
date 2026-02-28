#!/bin/sh
# Database backup script — called by cron at 00:00 daily
set -e

BACKUP_DIR="/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="kasir_toko_${DATE}.sql.gz"

# Keep only last 7 days of backups
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +7 -delete 2>/dev/null || true

echo "[$(date)] Starting backup..."

mysqldump \
  -h "${DB_HOST:-mariadb}" \
  -u "${DB_USER:-root}" \
  -p"${DB_PASS:-secret}" \
  "${DB_NAME:-kasir_toko}" \
  --single-transaction \
  --quick \
  --lock-tables=false \
  | gzip > "${BACKUP_DIR}/${FILENAME}"

SIZE=$(du -h "${BACKUP_DIR}/${FILENAME}" | cut -f1)
echo "[$(date)] ✅ Backup complete: ${FILENAME} (${SIZE})"
