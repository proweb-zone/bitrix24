FROM php:8.1-fpm
# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
nginx \
libfreetype6-dev \
libjpeg62-turbo-dev \
libpng-dev \
libzip-dev \
libicu-dev \
libonig-dev \
libxml2-dev \
libcurl4-openssl-dev \
libssl-dev \
libldap2-dev \
unzip \
git \
cron \
&& rm -rf /var/lib/apt/lists/*
# mysql-client \

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install -j$(nproc) \
gd \
zip \
intl \
mbstring \
xml \
curl \
mysqli \
pdo_mysql \
opcache \
ldap

# Установка расширения sockets
RUN docker-php-ext-install sockets

# Для некоторых версий может потребоваться:
# RUN docker-php-ext-install sockets && docker-php-ext-enable sockets

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Копирование конфигурации PHP
COPY php/php.ini /usr/local/etc/php/conf.d/bitrix.ini
# Создание рабочей директории
WORKDIR /var/www/html
# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
