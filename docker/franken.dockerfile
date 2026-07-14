FROM dunglas/frankenphp

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    libmemcached-dev \
    zlib1g-dev \
    libssl-dev \
    libpng-dev \
    libpq-dev

RUN install-php-extensions \
    iconv \
    memcached \
    redis \
    pgsql \
    mysqli \
    sockets \
    gd \
    zip \
    opcache

RUN mkdir -p /usr/local/bin 0775
RUN echo "#!/bin/sh" >> /usr/local/bin/php-fpm
RUN echo "docker-php-entrypoint --config /etc/caddy/Caddyfile --adapter caddyfile" >> /usr/local/bin/php-fpm
RUN chmod +x /usr/local/bin/php-fpm

