[![Build Status](https://travis-ci.org/jlekowski/designpatterns.svg)](https://travis-ci.org/jlekowski/designpatterns)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/88d176ba-ffc7-4241-b74b-79ee9d387063/mini.png)](https://insight.sensiolabs.com/projects/88d176ba-ffc7-4241-b74b-79ee9d387063)

# Battleships (API)

## Battleships (sea battle) game - REST API
API to manage users and interactions between them in games.

### DEMO
http://dev.lekowski.pl

### LINKS
* https://github.com/jlekowski/battleships - legacy full web version
* https://github.com/jlekowski/battleships-webclient - web client for the API 

## === Installation ===
1. Download and unzip or clone.
2. Setup the stack (web server, database etc.) - [SETUP.md](SETUP.md).
3. Change Symfony environment to production, install dependencies, and provide parameters.
```
export SYMFONY_ENV=prod
composer install --optimize-autoloader --no-dev
bin/console cache:clear --env=prod --no-debug

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
3. You can run E2E tests too.
```
bin/console test:e2e -v "battleships-api.localhost/v1"
```

## === Changelog ===
* version **1.0**
 * Working version of the API deployed 
 * Still many TODOs, but they should not affect the stability and will be fixed on an ongoing basis  
