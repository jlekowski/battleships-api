[![Build Status](https://travis-ci.org/jlekowski/battleships-api.svg?branch=master)](https://travis-ci.org/jlekowski/battleships-api)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/88d176ba-ffc7-4241-b74b-79ee9d387063/mini.png)](https://insight.sensiolabs.com/projects/88d176ba-ffc7-4241-b74b-79ee9d387063)
[![codecov](https://codecov.io/gh/jlekowski/battleships-api/branch/master/graph/badge.svg)](https://codecov.io/gh/jlekowski/battleships-api)

# Battleships (API)

## Battleships (sea battle) game - REST API
API to manage users and interactions between them in games.

### DEMO
http://dev.lekowski.pl

### DOCS
http://battleships-api.dev.lekowski.pl/doc

### LINKS
* https://github.com/jlekowski/battleships-webclient - web client for the API
* https://github.com/jlekowski/battleships-apiclient - PHP client for the API
* https://github.com/jlekowski/battleships-offline - offline version
* https://github.com/jlekowski/battleships - legacy full web version

## === Installation ===
1. Download and unzip or clone.
2. Setup the stack (web server, database etc.) - [SETUP](SETUP.md).
3. Change Symfony environment to production, install dependencies, and provide parameters.
```
export SYMFONY_ENV=prod
composer install --optimize-autoloader --no-dev --apcu-autoloader
bin/console cache:clear --env=prod --no-debug --no-warmup

bin/console doctrine:schema:validate
bin/console doctrine:schema:update --force
```
4. You may need to add privileges to var folder (http://symfony.com/doc/current/book/installation.html).
```
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
```

## === Test ===
1. Install dev dependencies.
```
composer install
```
2. Run unit tests.
```
bin/phpunit
```
3. You can run E2E tests (see https://github.com/jlekowski/battleships-apiclient)

## === Changelog ===
* version **1.5**
  * Upgraded to PHP 7.1 (composer.lock - `composer update --ignore-platform-reqs` on 7.0 still works)
  * Upgraded to Symfony 3.3 and all bundles and dependencies
  * Replaced env variables `SYMFONY__*` with `BATTLESHIPS_*` as deprecated in Symfony 3.3
  * Renamed game fields user_id1|2 to user1|2_id
  * Added cache clearers (opcache, varnish, apc) run on Symfony `cache:clear` command
  * Added phpstan to travis build

* version **1.4**
  * Added tests to reach 100% code coverage
  * Added Codecov support
  * Upgraded dependencies and support for PHP 7.2
  * Added `step` option to `UserTokenBreakCommand`
  * Added support for `SYMFONY__VAR_DIR` and `SYMFONY__DEV_ENABLED` env variables (see battleships-vagrant repository)
  * Changed cache tags format for game events

* version **1.3**
  * Dropped support for PHP 5.* version
  * Added ApiDoc under `/doc` (see http://battleships-api.dev.lekowski.pl/doc)

* version **1.2**
  * Fixed critical bug in sorting when setting some ships with mast in column 10
  * Small changes for build/analysis tools (PSR4 declaration, no 5.5 support)

* version **1.1**
  * Changed the way coordinates are handled
  * Moved E2E to battleships-apiclient repo
  * Updated dependencies
  * Cleaning and refactoring

* version **1.0**
  * Working version of the API deployed
  * Still many TODOs, but they should not affect the stability and will be fixed on an ongoing basis
