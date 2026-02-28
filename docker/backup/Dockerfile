FROM alpine:3.20

# install required tools (curl added for Telegram upload)
RUN apk add --no-cache mariadb-client gzip curl

COPY docker/backup/backup.sh /usr/local/bin/backup.sh
RUN chmod +x /usr/local/bin/backup.sh

# Cron job: run backup at 00:00 every day
RUN echo "0 0 * * * /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1" > /etc/crontabs/root

CMD ["crond", "-f", "-l", "2"]
