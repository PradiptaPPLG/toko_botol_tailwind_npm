FROM php:8.2-fpm-alpine

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install Node.js for TailwindCSS build
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

# Copy entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
