FROM php:8.2-apache

# Install ekstensi yang dibutuhkan CI4 (intl, gd, zip, mysqli)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl gd zip mysqli pdo_mysql

# Aktifkan mod_rewrite untuk Apache (Penting untuk routing CI4)
RUN a2enmod rewrite

# Set Document Root ke folder /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy project
COPY . /var/www/html/

# Set permission untuk folder writable
RUN chown -R www-data:www-data /var/www/html/writable

# Gunakan port yang diberikan Railway secara dinamis
EXPOSE 80