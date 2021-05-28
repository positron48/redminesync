FROM php:7.4-fpm-alpine

COPY wait-for-it.sh /usr/bin/wait-for-it

RUN chmod +x /usr/bin/wait-for-it

RUN apk --update --no-cache add git curl bash

RUN set -ex && apk --no-cache add postgresql-dev

RUN docker-php-ext-install pdo pdo_pgsql bcmath

# ext-zip https://github.com/docker-library/php/issues/797
RUN apk add --no-cache libzip-dev && \
    docker-php-ext-install zip

# ext-gd https://github.com/docker-library/php/issues/225
RUN apk add --no-cache freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev && \
    NPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) && \
    docker-php-ext-install -j${NPROC} gd && \
    apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD curl -sS https://get.symfony.com/cli/installer | bash

CMD mv /root/.symfony/bin/symfony /usr/local/bin/symfony

CMD composer install ; wait-for-it database:5432 ;  php-fpm

EXPOSE 9000
