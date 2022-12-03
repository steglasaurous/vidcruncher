FROM php:8.1-apache
# Install stuff needed locally for ffmpeg - used for splitting and assembling files
RUN apt-get -y update && apt-get -y install ffmpeg

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pgsql pdo_pgsql

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable rewrite
RUN a2enmod rewrite

COPY ./docker/web/conf.d/upload.ini $PHP_INI_DIR/conf.d/upload.ini

COPY . /var/www/html
