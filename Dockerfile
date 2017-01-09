FROM php:7.1-apache
COPY . /var/www/html
EXPOSE 80

RUN apt-get update

# Curl
RUN apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install -j$(nproc) curl