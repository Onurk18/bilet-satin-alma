# Dockerfile
FROM php:8.3-apache

# SQLite eklentileri
RUN apt-get update \
 && apt-get install -y libsqlite3-dev sqlite3 \
 && docker-php-ext-configure pdo_sqlite \
 && docker-php-ext-install pdo_sqlite \
 && rm -rf /var/lib/apt/lists/*

# Apache ayarı (public/ kök olsun)
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Kodları kopyala
COPY . /var/www/html

# storage izinleri
RUN mkdir -p /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/storage

# entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
