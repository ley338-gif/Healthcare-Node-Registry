FROM node:24.18.0-bookworm-slim@sha256:6f7b03f7c2c8e2e784dcf9295400527b9b1270fd37b7e9a7285cf83b6951452d AS frontend-build

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./
RUN npm run build

FROM php:8.4.24-fpm-bookworm@sha256:d19afbcc35ef6eae436be15439d9e0e96a000f40e95578ec86a6ddff3d66e4bb AS php-base

ARG COMPOSER_VERSION=2.8.12

RUN apt-get update \
    && apt-get upgrade -y \
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
    && pecl install pcov-1.0.12 \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/requirements-dicom.txt /tmp/requirements-dicom.txt

RUN python3 -m venv /opt/registry-dicom \
    && /opt/registry-dicom/bin/pip install --no-cache-dir --require-hashes -r /tmp/requirements-dicom.txt \
    && /opt/registry-dicom/bin/pip uninstall --yes pip setuptools \
    && rm /tmp/requirements-dicom.txt

RUN curl -fsSL "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" \
        -o /usr/local/bin/composer \
    && chmod 0755 /usr/local/bin/composer \
    && composer --version

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-registry.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/php/pcov.ini /usr/local/etc/php/conf.d/20-pcov.ini

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

FROM php-base AS development

FROM php-base AS release-build

COPY --chown=www-data:www-data . /var/www/html

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --classmap-authoritative \
    && rm -rf /var/www/.composer/cache/* \
    && php artisan package:discover --ansi

COPY --from=frontend-build --chown=www-data:www-data /build/public/build /var/www/html/public/build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# The runtime starts again from the unmodified PHP image and only receives the
# compiled extensions, the DICOM venv and the built application. Compilers,
# headers, Composer, git, pip and pcov from php-base never reach the release.
FROM php:8.4.24-fpm-bookworm@sha256:d19afbcc35ef6eae436be15439d9e0e96a000f40e95578ec86a6ddff3d66e4bb AS release-app

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        dcmtk \
        iputils-ping \
        libicu72 \
        libpq5 \
        libzip4 \
        python3 \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS libc6-dev curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=php-base /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-base /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=php-base /opt/registry-dicom /opt/registry-dicom
COPY --from=php-base /usr/local/bin/registry-entrypoint /usr/local/bin/registry-entrypoint

RUN rm \
        "$(php-config --extension-dir)/pcov.so" \
        /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini \
        /usr/local/etc/php/conf.d/20-pcov.ini

ARG APP_VERSION=0.9.0-rc.2
ARG VCS_REF=unknown

LABEL org.opencontainers.image.title="Healthcare Node Registry" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.source="https://github.com/ley338-gif/Healthcare-Node-Registry"

ENV APP_VERSION="${APP_VERSION}"

WORKDIR /var/www/html

COPY --from=release-build /var/www/html /var/www/html

ENTRYPOINT ["/usr/local/bin/registry-entrypoint"]

CMD ["php-fpm", "-F"]

FROM nginx:1.30.4-alpine3.24@sha256:97d490c12ba55b4946b01546d1c3ed324e8d41ab1c9fcb2a616aa470620e5b46 AS release-web

RUN apk upgrade --no-cache

ARG APP_VERSION=0.9.0-rc.2
ARG VCS_REF=unknown

LABEL org.opencontainers.image.title="Healthcare Node Registry Web" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.source="https://github.com/ley338-gif/Healthcare-Node-Registry"

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=frontend-build /build/public/build /var/www/html/public/build
