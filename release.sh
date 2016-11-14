#!/bin/bash

export SYMFONY_ENV=prod
~/composer.phar install --optimize-autoloader --no-dev
bin/console cache:clear --env=prod --no-debug
bin/console cache:apc:clear

bin/console doctrine:schema:validate

sudo varnishadm "ban req.url ~ /"