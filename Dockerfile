FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring xml bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && touch database/database.sqlite \
    && php artisan migrate --force \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
