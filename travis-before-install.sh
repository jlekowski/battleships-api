#!/bin/bash

if [[ $TRAVIS_PHP_VERSION =~ 5.[56] ]]
then
    yes '' | pecl install apcu-4.0.10
elif [[ $TRAVIS_PHP_VERSION =~ 7.*|nightly ]]
then
    pear config-set preferred_state beta
    echo 'extension = apc.so' >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
    yes '' | pecl install apcu_bc
fi

cp app/config/parameters.yml.dist app/config/parameters.yml
sed -i -e 's/database_user:.*/database_user: travis/' app/config/parameters.yml
sed -i -e 's/secret:.*/secret: travis/' app/config/parameters.yml

mysql -e 'CREATE DATABASE battleships CHARACTER SET = utf8mb4 COLLATE utf8mb4_unicode_ci;'
