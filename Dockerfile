<<<<<<< HEAD
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-interaction --optimize-autoloader
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8082

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8082"]
=======
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

EXPOSE 8000
>>>>>>> 7ac1a7921f12b5cdc88acee4b928ef25e4e2bc0d
