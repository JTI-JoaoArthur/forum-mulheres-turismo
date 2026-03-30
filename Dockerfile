FROM php:8.2-apache

RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/admin/data /var/www/html/admin/uploads 2>/dev/null; \
    mkdir -p /var/www/html/admin/data /var/www/html/admin/uploads \
    && chown -R www-data:www-data /var/www/html/admin/data /var/www/html/admin/uploads

EXPOSE 80
