FROM php:8.2-fpm

# Dependencias del sistema, Nginx y librerías para extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    nginx \
    && docker-php-ext-install \
        gd \
        intl \
        zip \
        pdo_mysql \
        bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Código del proyecto
COPY . .

# Dependencias de PHP sin dev
RUN composer install --no-dev --optimize-autoloader

# PHP-FPM: dejar pasar las variables de entorno de Render a PHP y mostrar errores en los logs
RUN printf '[www]\n\
clear_env = no\n\
catch_workers_output = yes\n\
decorate_workers_output = no\n' > /usr/local/etc/php-fpm.d/zz-render.conf

# Plantilla de Nginx (el puerto se reemplaza al iniciar)
RUN printf 'server {\n\
    listen __PORT__;\n\
    server_name _;\n\
    root /var/www/html/public;\n\
    index index.php index.html;\n\
    client_max_body_size 50M;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}\n' > /etc/nginx/sites-available/default.template

# Script de arranque
RUN printf '#!/bin/sh\n\
sed "s/__PORT__/${PORT:-10000}/" /etc/nginx/sites-available/default.template > /etc/nginx/sites-available/default\n\
cd /var/www/html\n\
php artisan config:clear || true\n\
php artisan storage:link || true\n\
php-fpm -D\n\
exec nginx -g "daemon off;"\n' > /start.sh && chmod +x /start.sh

# Permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

CMD ["/start.sh"]
