# Usamos PHP 8.2 con Apache
FROM php:8.2-apache

# 1. Instalamos certificados SSL del sistema y la extensión mysqli
RUN apt-get update && apt-get install -y ca-certificates \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli
    
# Copiamos todos tus archivos (.php, .html, etc.) al servidor
COPY . /var/www/html/

# Damos permisos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html/

# Exponemos el puerto 80
EXPOSE 80

