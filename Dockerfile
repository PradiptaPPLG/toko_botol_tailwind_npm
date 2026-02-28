# ──────────────────────────────────────────────
# Stage 1: Build TailwindCSS (thrown away after)
# ──────────────────────────────────────────────
FROM node:20-alpine AS build

WORKDIR /build

# Install deps first (cache-friendly layer)
COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline

# Copy only what TailwindCSS needs to scan
COPY input.css tailwind.config.js ./
COPY src/ ./src/

# Build minified CSS
RUN npx @tailwindcss/cli -i input.css -o ./dist/tailwind.css --minify

# ──────────────────────────────────────────────
# Stage 2: Production PHP image (no Node.js!)
# ──────────────────────────────────────────────
FROM php:8.2-fpm-alpine

# Install mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

WORKDIR /var/www/html

# Copy application source
COPY src/ ./src/
COPY migrate ./migrate

# Copy pre-built CSS from build stage
COPY --from=build /build/dist/ ./dist/

# Copy entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
