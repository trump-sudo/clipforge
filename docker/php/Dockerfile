FROM php:8.3-fpm-bookworm

# ffmpeg + imagemagick for the media pipeline; pcntl lets queue:work enforce job timeouts.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg imagemagick git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-clipforge.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
