FROM php:8.1-fpm

RUN apt-get update -yqq
RUN apt-get install zip -yqq

# Set de la timezone => Paris
ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN dpkg-reconfigure -f noninteractive tzdata

# Dépendances pour PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

# Dépendances pour PHP
RUN install-php-extensions apcu \
    bcmath \
    calendar \
    intl \
    http \
	gd \
	opcache \
    pdo_pgsql \
    pgsql \
    xml \
    xsl \
	xdebug \
    zip

# Config PHP
COPY symfony.ini $PHP_INI_DIR/conf.d/
COPY symfony.pool.conf /usr/local/etc/php-fpm.d/
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY enxdebug.sh /usr/local/bin
RUN chmod 744 /usr/local/bin/enxdebug.sh

RUN cat /usr/local/etc/php/conf.d/xdebug.ini >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && rm /usr/local/etc/php/conf.d/xdebug.ini

RUN /usr/local/bin/enxdebug.sh off

# Ajout Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -sL https://deb.nodesource.com/setup_14.x | bash - && apt-get update && apt-get install -y nodejs
RUN npm install -g yarn

WORKDIR /var/www/sfcastapiplatform

COPY init.sh /init.sh
RUN chmod +x /init.sh

EXPOSE 9001

CMD ["php-fpm"]
