FROM php:cli
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y \
        unzip \
        nodejs \
        npm \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) zip