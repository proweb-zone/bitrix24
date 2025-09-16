FROM php:8.1-apache

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo \
    pdo_mysql \
    intl \
    mbstring \
    zip \
    xml \
    curl \
    opcache \
    sockets

# Включение mod_rewrite
RUN a2enmod rewrite headers ssl

# Настройка PHP для Bitrix
RUN { \
    echo 'memory_limit = 512M'; \
    echo 'upload_max_filesize = 128M'; \
    echo 'post_max_size = 128M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'date.timezone = Europe/Moscow'; \
} > /usr/local/etc/php/conf.d/bitrix.ini

# Создание директории для сайта
WORKDIR /var/www/html

EXPOSE 80 443
