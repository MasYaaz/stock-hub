FROM php:8.2-fpm-alpine

# 1. Install dependensi sistem
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    curl

# 2. Install ekstensi PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl gd zip mysqli pdo_mysql mbstring

# 3. KUNCI PERBAIKAN: Install Composer secara resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 4. Copy file konfigurasi Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

# 5. Copy file composer dahulu (untuk optimasi cache)
COPY composer.json composer.lock ./

# 6. Jalankan instalasi vendor (tanpa script dev untuk hemat ruang)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Copy seluruh project
COPY . .

# 8. Atur Permission
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# 9. Script starter dengan Migrasi dan Stock Sync otomatis
RUN echo "#!/bin/sh" > /start.sh && \
    echo "echo 'Running migrations...'" >> /start.sh && \
    echo "php spark migrate --all || echo 'Migration failed'" >> /start.sh && \
    \
    # Jalankan sync di BACKGROUND (pake simbol &)
    # Supaya Nginx gak nungguin sync selesai
    # echo "echo 'Syncing stock data in background...'" >> /start.sh && \
    # echo "php spark stock:sync > /dev/null 2>&1 &" >> /start.sh && \
    # \
    # Jalankan service utama
    echo "php-fpm -D" >> /start.sh && \
    echo "nginx -g 'daemon off;'" >> /start.sh && \
    chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]