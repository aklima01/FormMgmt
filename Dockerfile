FROM webdevops/php-nginx:8.2

WORKDIR /app

COPY ./app /app

ENV WEB_DOCUMENT_ROOT=/app/public

ENV APP_ENV=dev


ARG DATABASE_URL
ENV DATABASE_URL=${DATABASE_URL}

# Disable opcache for CLI to prevent segfault during composer install
RUN echo "opcache.enable_cli=0" > /opt/docker/etc/php/php.ini

# Prevent memory exhaustion issues
ENV COMPOSER_MEMORY_LIMIT=-1

RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts
