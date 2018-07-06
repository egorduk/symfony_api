#!/usr/bin/env bash
set -e

CONFIG_DIST_FILE=/app/app/config/parameters_docker_distr.yml
CONFIG_PROD_FILE=/app/app/config/parameters_docker.yml

rm -rf /data/*
#cp -R /app/web/dist /data/assets

function setup_configuration {
    cat ${CONFIG_DIST_FILE} | beaver | tee ${CONFIG_PROD_FILE}
    /app/app/console --env=docker --no-interaction doctrine:migrations:migrate
    /app/app/console --env=docker --no-interaction cache:clear
    /app/app/console --env=docker --no-interaction doctrine:cache:clear-metadata
    /app/app/console --env=docker --no-interaction doctrine:cache:clear-query
    /app/app/console --env=docker --no-interaction doctrine:cache:clear-result
}

setup_configuration

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

# Double check cause sometimes configuration doesn't change
if [ ! -f ${CONFIG_PROD_FILE} ]; then
    setup_configuration
fi

/bin/chown www-data:www-data /app/app/cache -R
/bin/chown www-data:www-data /app/app/logs -R
/bin/chown www-data:www-data /uploads -R

exec "$@"