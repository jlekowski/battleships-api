#!/bin/bash

export SYMFONY_ENV=prod

composer install --optimize-autoloader --no-dev --apcu-autoloader

bin/console cache:clear --env=prod --no-debug --no-warmup
bin/console cache:warmup --env=prod

bin/console doctrine:schema:validate
bin/console doctrine:schema:update --force
