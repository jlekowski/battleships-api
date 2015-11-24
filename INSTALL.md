# http://symfony.com/doc/current/cookbook/composer.html
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# http://symfony.com/doc/current/book/installation.html
sudo curl -LsS http://symfony.com/installer -o /usr/local/bin/symfony
sudo chmod a+x /usr/local/bin/symfony

# while it requires at least PHP 5.5, it uses APC so for compatibility go with
sudo apt-get install php5-apcu

export SENSIOLABS_ENABLE_NEW_DIRECTORY_STRUCTURE=true
# symfony new rest-api-php7
composer create-project symfony/framework-standard-edition battleships-api
cd battleships-api
composer update

php bin/security-checker security:check

HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs


composer require "jms/serializer-bundle" "@stable"
composer require "friendsofsymfony/rest-bundle" "@stable"

CREATE DATABASE battleships CHARACTER SET = utf8mb4 COLLATE utf8mb4_unicode_ci;
# GRANT ALL PRIVILEGES ON battleships.* TO 'test'@'%' WITH GRANT OPTION;

php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --force
php bin/console doctrine:schema:validate

# adding in apache VHOST to catch Authorization headers:
            RewriteCond %{HTTP:Authorization} ^(.*)
            RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]

# deploy to production
export SYMFONY_ENV=prod
composer install --optimize-autoloader --no-dev
php bin/console cache:clear --env=prod --no-debug

# Setup OPCache - edit php.ini (e.g. /etc/php5/apache2/php.ini)
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.save_comments=1
opcache.fast_shutdown=1

# PLAN
- create all end points (hash probably, not id yet)
- go with the DB as it used to be before (think while doing it how to improve it, like user table)
- combine end points with the DB resources
- add unit tests (and e2e from command?)
- caching (YML file at least, but maybe also redis?)
- improve REST and DB structure, authorisation

# DB
- game (id, user1_id, user2_id, user1_ships, user2_ships, timestamp)
- user table (id, name, email)
- shot table (id, game_id, user_id, coords, result, timestamp)
- chat table (id, game_id, user_id, text, timestamp)
- event/log table (id, game_id, user_id, type, value, timestamp)