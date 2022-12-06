FROM php:8.1-apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install stuff needed locally for ffmpeg - used for splitting and assembling files
RUN apt-get -y update && apt-get -y install ffmpeg git unzip \
    && install-php-extensions pgsql pdo_pgsql zip \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

COPY ./docker/common/conf.d/upload.ini $PHP_INI_DIR/conf.d/upload.ini
ADD . /var/www/html

RUN cd /var/www/html \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && ls -l \
    && php composer.phar -n install
