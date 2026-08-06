FROM php:8.4.16-fpm-bookworm

ARG COMPOSER_VERSION=2.8.12

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        dcmtk \
        git \
        iputils-ping \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        python3 \
        python3-pip \
        python3-venv \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/requirements-dicom.txt /tmp/requirements-dicom.txt

RUN python3 -m venv /opt/registry-dicom \
    && /opt/registry-dicom/bin/pip install --no-cache-dir --require-hashes -r /tmp/requirements-dicom.txt \
    && rm /tmp/requirements-dicom.txt

RUN curl -fsSL "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" \
        -o /usr/local/bin/composer \
    && chmod 0755 /usr/local/bin/composer \
    && composer --version

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-registry.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

WORKDIR /var/www/html

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /var/www/.composer/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        /var/www/.composer \
    && git config --system --add safe.directory /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/registry-entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/registry-entrypoint \
    && chmod 0755 /usr/local/bin/registry-entrypoint

ENTRYPOINT ["/usr/local/bin/registry-entrypoint"]

CMD ["php-fpm", "-F"]
