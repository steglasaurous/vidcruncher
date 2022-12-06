FROM ubuntu:kinetic
# Encoder - needs PHP CLI and FFMPEG 5.1.1+, so using ubuntu's latest which includes what we need.
ENV DEBIAN_FRONTEND=noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN=true
COPY ./docker/encoder/preseed.txt /preseed.txt
RUN debconf-set-selections /preseed.txt

RUN apt-get -y update && apt-get -y install ffmpeg php-cli php-pdo-pgsql php-curl php-xml git unzip \
    && mkdir -p /var/www/html

ADD . /var/www/html

RUN cd /var/www/html \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && ls -l \
    && php composer.phar -n install

CMD [ "php", "/var/www/html/bin/console", "messenger:consume", "encoder", "-vv" ]
