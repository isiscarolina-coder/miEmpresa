# Usamos PHP 8.2 con Apache
FROM php:8.2-apache

# Instalamos la extensión mysqli que es la que usas en tu código
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Instalamos y habilitamos OpenSSL para la conexión segura con TiDB
RUN apt-get update && apt-get install -y openssl

# Copiamos todos tus archivos (.php, .html, etc.) al servidor
COPY . /var/www/html/

# Damos permisos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html/

# Exponemos el puerto 80
EXPOSE 80
