FROM php:8

WORKDIR /app
COPY . /app
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
RUN apt update && apt install unzip
RUN composer install --ignore-platform-reqs
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql



CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8080"]
EXPOSE 8080
