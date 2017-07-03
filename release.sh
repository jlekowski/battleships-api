#!/bin/bash

export SYMFONY_ENV=prod
~/composer.phar install --optimize-autoloader --no-dev --apcu-autoloader
bin/console cache:clear --env=prod --no-debug --no-warmup
bin/console cache:warmup --env=prod
bin/console cache:apc:clear

bin/console doctrine:schema:validate

sudo varnishadm "ban req.url ~ /"
