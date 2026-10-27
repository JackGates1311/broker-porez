# PHP za pokretanje testova nad MariaDB-om (docker/test/testovi.sh).
# bcmath: obračun poreza (BcMath\Number); pdo_mysql: Laravel; mysqli: Tests\Baza\PripremaBaze učitava sql/ fajlove.
# Alpine umesto Debian varijante: mnogo manje sistemskih paketa, pa i prijavljenih ranjivosti (Docker Scout).
FROM php:8.4-cli-alpine

# apk upgrade: bezbednosne ispravke paketa izašle posle baznog image-a (npr. curl, brotli).
# Alati za kompajliranje (PHPIZE_DEPS) samo za vreme instalacije ekstenzija, pa se brišu u istom sloju.
RUN apk upgrade --no-cache \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install bcmath pdo_mysql mysqli \
    && apk del .build-deps

WORKDIR /app

CMD ["php", "artisan", "test"]
