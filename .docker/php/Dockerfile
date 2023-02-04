FROM mileschou/phalcon:7.4-fpm-alpine

# Install PHP extensions
RUN apk add icu-dev
RUN docker-php-ext-install pdo pdo_mysql intl

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/php
