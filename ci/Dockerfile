ARG PHP_VERSION
FROM php:$PHP_VERSION
RUN curl https://getcomposer.org/download/latest-2.x/composer.phar -o /usr/bin/composer.phar && chmod a+x /usr/bin/composer.phar
WORKDIR /tolerant-php-parser
RUN apt-get update && apt-get install -y unzip && apt-get clean
ARG COMPOSER_OPTIONS
ENV COMPOSER_OPTIONS=$COMPOSER_OPTIONS

ADD composer.json ./
RUN composer.phar install $COMPOSER_OPTIONS && composer.phar clear-cache

ADD . .
