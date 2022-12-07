FROM php:8.1-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install stuff needed locally for ffmpeg - used for splitting and assembling files
RUN apt-get -y update && apt-get -y install ffmpeg git unzip \
    && install-php-extensions pgsql pdo_pgsql zip

COPY ./docker/common/conf.d/upload.ini $PHP_INI_DIR/conf.d/upload.ini
COPY ./docker/cron/run.sh /run.sh
COPY ./docker/common/wait-for-it.sh /wait-for-it.sh
ADD . /var/www/html

RUN cd /var/www/html \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && ls -l \
    && php composer.phar -n install

#CMD ["/bin/bash", "-c", "/run.sh"]
CMD ["/wait-for-it.sh", "database:5432", "--", "/run.sh"]
