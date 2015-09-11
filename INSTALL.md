# http://symfony.com/doc/current/cookbook/composer.html
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# http://symfony.com/doc/current/book/installation.html
sudo curl -LsS http://symfony.com/installer -o /usr/local/bin/symfony
sudo chmod a+x /usr/local/bin/symfony

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
