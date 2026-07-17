# Imagen de la aplicación: PHP 8.3 + Apache, usuario no root, puerto 8080.
FROM php:8.5-apache

# Extensión de MySQL y módulos de Apache (rewrite para el front controller,
# headers para las cabeceras de seguridad, deflate para compresión gzip).
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers deflate

# Apache escucha en 8080 para poder ejecutarse sin root, con el DocumentRoot
# apuntando a public/ (el resto del código queda fuera del árbol servible).
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && mkdir -p /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

# Configuración de PHP para producción (los errores van al log, no a la respuesta).
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html
COPY . .
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/www/html/var \
    && chown -R www-data:www-data /var/www/html/var

USER www-data
EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
