ARG PHP_VERSION="8.3"

# ─── Stage 1: base — PHP runtime + extensions + Composer ─────────────────────
FROM php:${PHP_VERSION}-fpm AS base

LABEL maintainer="Nub Labs <hello@nublabs.com>"
LABEL description="TechHeaven Reference Platform — runtime image (Bagisto lives on the host)"

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libavif-dev \
    libxpm-dev \
    libmagickwand-dev \
    default-mysql-client \
    procps \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        soap \
        xml \
        calendar

RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install imagick && docker-php-ext-enable imagick

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/techheaven.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/techheaven.conf

WORKDIR /var/www/html

# ─── Stage 2: app — adds Nginx + Supervisor ───────────────────────────────────
FROM base AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]

# ─── Stage 3: worker — php-fpm only (queue / scheduler) ──────────────────────
FROM base AS worker

COPY docker/worker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "queue:work", "redis", "--sleep=3", "--tries=3", "--max-time=3600"]
