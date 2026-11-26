# PHP 8.3 + Apache, similar al PHP 8.2+ de Plesk
FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql \
 && apt-get update && apt-get install -y --no-install-recommends msmtp \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers
COPY php-local.ini /usr/local/etc/php/conf.d/zz-local.ini
COPY apache-local.conf /etc/apache2/sites-available/000-default.conf
