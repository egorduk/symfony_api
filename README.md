# Exmarkets

The best bitcoin market

## Installation

Install composer if you haven't yet:

    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer

Clone project

    git clone git@pm.datajob.lt:btc-x/btc-x.git /yourwebroot/btc
    cd /yourwebroot/btc

Customize parameters:

    cp app/config/parameters.yml.dist app/config/parameters.yml
    vim app/config/parameters.yml

    cp app/config/bundle/nsq.yml.dist app/config/bundle/nsq.yml
    vim app/config/bundle/nsq.yml

    cp behat.yml.dist behat.yml
    vim behat.yml

Make sure you meet symfony2 dependencies:

    php app/check.php

Continue with installation:

    mkdir app/{cache,logs}
    npm install
    composer install
    gulp
    ./bin/app demo

### Githooks

In order to prevent **you** from commiting vardumps or dies into source tree. Install githooks:

    ./bin/githooks

### Nginx

If you use apache - don't :) Here are some nginx config samples:
Make sure your fpm socket is in the right place

``` nginx
server {
    listen 80;

    server_name bic.lc;
    root /home/gedi/php/bic/web;

    error_log /var/log/nginx/bic.error.log;
    access_log /var/log/nginx/bic.access.log;

    rewrite ^/app_dev\.php?(.*)$ /$1 permanent;

    location / {
        index app_dev.php;
        if (-f $request_filename) {
            break;
        }
        rewrite ^(.*)$ /app_dev.php last;
    }

    location ~ ^/app_dev\.php(/|$) {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }
}

server {
    listen 80;

    server_name bic-prod.lc;
    root /home/gedi/php/bic/web;

    error_log /var/log/nginx/bic.error.log;
    access_log /var/log/nginx/bic.access.log;

    rewrite ^/app\.php?(.*)$ /$1 permanent;

    location / {
        index app.php;
        if (-f $request_filename) {
            break;
        }
        rewrite ^(.*)$ /app.php last;
    }

    location ~ ^/app\.php(/|$) {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }
}
```

### Trade engine

Trade engine is a tool written in **golang** to process deals(orders) submited in markets effectivelly.

First you need to properly install **go** and place the trade-engine package where
it belongs in **$GOPATH** refer to [trade-engine](http://pm.datajob.lt/btc-x/go-tradeengine/blob/master/README.md)

### Job Queue

BTC uses [beanstalkd](http://kr.github.io/beanstalkd/) to manage a job queue. You will need to
install it locally and run as a deamon. Default configuration parameters will be inherited from
**app/config/parameters.yml.dist**

## Testing

Our app is tested with **Behat**, **Phpspec** and **Jasmine**

### PhpSpec

Run all tests in quick standard format:

    ./bin/phpspec run

Run specs in detailed format

    ./bin/phpspec run --format=pretty

Run a specific spec:

    ./bin/phpspec run spec/Btc/UserBundle/Menu/BuilderSpec.php

### Behat

You will also need to prepare testing environment, since behat is using different database:

    ./bin/app reload -e test

Start test server:

    ./bin/test-server start

To run all behat features:

    ./bin/behat

Run a specific feature:

    ./bin/behat features/user/login.feature

Run a specific scenario, indicated by line number:

    ./bin/behat features/user/login.feature:12

### Jasmine

To run all jasmine tests:

    gulp jasmine

## Gulpjs

Gulpjs docs: http://gulpjs.com

Firstly install nodejs from http://nodejs.org

Install other packages:

    npm install

To build all assets & for release:

    gulp

    if  - Error: ENOENT: no such file or directory, scandir '/data/frontend/node_modules/node-sass/vendor'

    resolve  - npm rebuild node-sass

To watch:

    gulp watch

To watch after build:

    gulp go

To build bundle.js with Vue.js:

    gulp build:js
    
To watch bundle.js with Vue.js:

    gulp watch:js
    
To build bundle.js with Vue.js for production:

    gulp build:js:release

To build a release package for production playbook:

    gulp package

To build package for staging playbook:

    gulp package --staging

**NOTE:** you must export playbook locations:

    export EXMARKETS_PLAYBOOK=/path/playbook-deployment

