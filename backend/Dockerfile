# syntax=docker/dockerfile:1

# Laravel API development image.
# Versions are pinned on purpose; bump them deliberately.
FROM composer:2.10.3 AS composer

FROM php:8.4.26-cli-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        bcmath \
        pcntl \
        opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-dualread.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

# Run as an unprivileged user whose UID/GID match the host user,
# so files created in the bind-mounted source (vendor/, storage/) stay editable.
RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash app \
    && chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
USER app

EXPOSE 8000

# Several workers so Nuxt SSR and browser requests don't queue behind each other.
ENV PHP_CLI_SERVER_WORKERS=4

# PHP's built-in server with Laravel's router, instead of `php artisan serve`:
# artisan serve strips most environment variables (DB_* etc.) from its workers,
# which would ignore the configuration injected by docker compose.
ENTRYPOINT ["entrypoint"]
# Laravel's router resolves the public directory from the working directory.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
