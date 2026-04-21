FROM php:8.2-apache

# 1. Install ekstensi (tetap sama)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl gd zip mysqli pdo_mysql

# 2. CARA AMPUH: Hapus paksa semua load file MPM, lalu aktifkan prefork saja
RUN rm -f /etc/apache2/mods-enabled/mpm_* && \
    a2enmod mpm_prefork

# 3. Aktifkan mod_rewrite
RUN a2enmod rewrite

# 4. Set Document Root (tetap sama)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Copy project & Permissions
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/writable && chmod -R 775 /var/www/html/writable

# 6. Pastikan EXPOSE 80 (Dan nanti di dashboard Railway set Networking ke 80)
EXPOSE 80

CMD ["apache2-foreground"]