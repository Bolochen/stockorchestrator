FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    git \
    curl \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    mbstring \
    bcmath \
    zip \
    intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --prefer-dist --no-interaction

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/bootstrap/cache /var/www/storage

EXPOSE 9000

CMD ["php-fpm"]


