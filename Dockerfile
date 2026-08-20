FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libxml2-dev unzip \
    && docker-php-ext-install dom mbstring xmlwriter \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php", "-a"]
