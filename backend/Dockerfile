FROM php:8.5-cli-alpine

RUN apk add --no-cache libcurl oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev oniguruma-dev \
    && docker-php-ext-install curl mbstring \
    && apk del .build-deps

WORKDIR /app

COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public router.php"]
