FROM webdevops/php-nginx:8.2

WORKDIR /app

COPY ./app /app

ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=prod

ARG DATABASE_URL
ENV DATABASE_URL=${DATABASE_URL}

ARG APP_SECRET
ENV APP_SECRET=${APP_SECRET}

ARG R2_ACCOUNT_ID
ENV R2_ACCOUNT_ID=${R2_ACCOUNT_ID}

ARG R2_ACCESS_KEY
ENV R2_ACCESS_KEY=${R2_ACCESS_KEY}

ARG R2_SECRET_KEY
ENV R2_SECRET_KEY=${R2_SECRET_KEY}

ARG R2_BUCKET
ENV R2_BUCKET=${R2_BUCKET}

ARG R2_REGION
ENV R2_REGION=${R2_REGION}

ARG R2_PUBLIC_URL
ENV R2_PUBLIC_URL=${R2_PUBLIC_URL}

# Disable opcache for CLI to prevent segfault during composer install
RUN echo "opcache.enable_cli=0" > /opt/docker/etc/php/php.ini

# Prevent memory exhaustion issues
ENV COMPOSER_MEMORY_LIMIT=-1

RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts