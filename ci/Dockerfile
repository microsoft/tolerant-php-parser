ARG PHP_VERSION
FROM php:$PHP_VERSION
RUN curl https://getcomposer.org/download/latest-2.x/composer.phar -o /usr/bin/composer.phar && chmod a+x /usr/bin/composer.phar
WORKDIR /tolerant-php-parser
# Debian buster (used by the php:7.2 image) has reached end-of-life and was
# removed from the regular Debian mirrors, so point apt at the archive mirror
# for that release before updating.
RUN if grep -q buster /etc/os-release 2>/dev/null; then \
        sed -i \
            -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
            -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' \
            /etc/apt/sources.list; \
    fi \
    && apt-get update && apt-get install -y unzip && apt-get clean
ARG COMPOSER_OPTIONS
ENV COMPOSER_OPTIONS=$COMPOSER_OPTIONS

ADD composer.json ./
RUN composer.phar install $COMPOSER_OPTIONS && composer.phar clear-cache

ADD . .
