FROM php:8.2-apache

# Instalamos certificados de CA (necesarios para SSL de TiDB) y la extensión mysqli
RUN apt-get update && apt-get install -y ca-certificates \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# Copiamos tus archivos PHP al contenedor
COPY . /var/www/html/

# Ajustamos permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80


