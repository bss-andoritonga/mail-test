FROM php:7.4-apache
WORKDIR /
RUN apt-get update && apt-get install -y git
RUN a2enmod rewrite
RUN apt-get install -y wget
RUN docker-php-ext-install -j "$(nproc)" opcache
RUN docker-php-ext-install pdo_mysql mysqli
RUN apt-get install -y libxml2-dev
RUN docker-php-ext-install soap

RUN set -ex; \
  { \
    echo "; Cloud Run enforces memory & timeouts"; \
    echo "memory_limit = -1"; \
    echo "max_execution_time = 0"; \
    echo "; File upload at Cloud Run network limit"; \
    echo "upload_max_filesize = 32M"; \
    echo "post_max_size = 32M"; \
    echo "; Configure Opcache for Containers"; \
    echo "opcache.enable = On"; \
    echo "opcache.validate_timestamps = Off"; \
    echo "; Configure Opcache Memory (Application-specific)"; \
    echo "opcache.memory_consumption = 32"; \
    echo "extension=soap"; \
  } > "$PHP_INI_DIR/conf.d/cloud-run.ini"
  # Copy in custom code from the host machine.
COPY . /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN apt-cache search php | grep -i soap
WORKDIR /var/www/html/
RUN composer update
RUN composer install
# Use the PORT environment variable in Apache configuration files.
# https://cloud.google.com/run/docs/reference/container-contract#port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Configure PHP for development.
# Switch to the production php.ini for production operations.
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# https://github.com/docker-library/docs/blob/master/php/README.md#configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
