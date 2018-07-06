FROM docker-php-builder
LABEL maintainer="Alex Shinkevich <a.shinkevich@besk.com>"

# Copy sources and install composer
COPY . /app

VOLUME /data
VOLUME /app/app/logs
VOLUME /app/app/cache

WORKDIR  /app

# Install Postgre PDO
RUN apt-get install -y libpq-dev

# Install php requirements
RUN php -d memory_limit=-1 /usr/local/bin/composer install


RUN chmod -R a+w /app/app

RUN /bin/chown www-data:www-data /app/app/logs
RUN /bin/chown www-data:www-data /app/app/cache
RUN ls -la /app/app

COPY docker-php-entrypoint.sh /usr/local/bin/docker-php-entrypoint
ENTRYPOINT ["docker-php-entrypoint"]

# Start php-fpm
CMD ["php-fpm"]