FROM php:fpm

ADD ./oracle/instantclient.zip /
ADD ./oracle/instantclient-sdk.zip /

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libzip-dev \
        libmemcached-dev \
        libpng-dev \
        libpq-dev \
        zip \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) pgsql \
    && docker-php-ext-install -j$(nproc) mysqli \
    && docker-php-ext-install -j$(nproc) sockets \
    && docker-php-ext-install -j$(nproc) zip

RUN pecl install redis && docker-php-ext-enable redis

RUN mkdir /opt/oracle
RUN apt-get install libaio1 libaio-dev
# Install Oracle Instantclient
RUN unzip /instantclient.zip -d /opt/oracle \
    && rm /instantclient.zip \
    && unzip /instantclient-sdk.zip -d /opt/oracle \
    && rm /instantclient-sdk.zip \
    && echo /opt/oracle/instantclient_19_19 > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

# add oracle instantclient path to environment
ENV LD_LIBRARY_PATH /opt/oracle/instantclient_19_19/

# Install Oracle extensions
RUN echo 'instantclient,/opt/oracle/instantclient_19_19/' | pecl install oci8 \
    && docker-php-ext-enable oci8