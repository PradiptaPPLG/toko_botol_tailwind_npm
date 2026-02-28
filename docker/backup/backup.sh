#!/bin/sh
# Database backup script — called by cron at 00:00 daily
set -e

BACKUP_DIR="/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="pdk_${DATE}.sql.gz"

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

# send file to Telegram if credentials are provided
if [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ]; then
  echo "[$(date)] Uploading to Telegram…"
  curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendDocument" \
       -F chat_id="${TELEGRAM_CHAT_ID}" \
       -F document=@"${BACKUP_DIR}/${FILENAME}" \
       -F caption="backup ${DB_NAME} ${DATE}" \
       || echo "[$(date)] ⚠️ Telegram upload failed"
fi
