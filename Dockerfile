FROM php:8.2-apache

# Instalamos certificados de CA (necesarios para SSL de TiDB) y la extensión mysqli
RUN apt-get update && apt-get install -y ca-certificates \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# Copiamos tus archivos PHP al contenedor
COPY . /var/www/html/

# Ajustamos permisos: carpetas 755, archivos 644, propietario www-data
RUN find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chown -R www-data:www-data /var/www/html

# Exponemos el puerto HTTP
EXPOSE 80

