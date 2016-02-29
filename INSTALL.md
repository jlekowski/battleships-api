# http://symfony.com/doc/current/cookbook/composer.html
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# http://symfony.com/doc/current/book/installation.html
sudo curl -LsS http://symfony.com/installer -o /usr/local/bin/symfony
sudo chmod a+x /usr/local/bin/symfony

# while it requires at least PHP 5.5, it uses APC so for compatibility go with
sudo apt-get install php5-apcu

# as in http://symfony.com/doc/current/book/installation.html
# symfony 2 only
export SENSIOLABS_ENABLE_NEW_DIRECTORY_STRUCTURE=true
# for PHP >= 5.4
symfony new battleships-api
# for PHP == 5.3
composer create-project symfony/framework-standard-edition battleships-api
cd battleships-api
composer update

# in Symfony 2
bin/security-checker security:check
# in Symfony 3
bin/console security:check

# From http://symfony.com/doc/current/book/installation.html
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
# or
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo chmod +a "$HTTPDUSER allow delete,write,append,file_inherit,directory_inherit" var
sudo chmod +a "`whoami` allow delete,write,append,file_inherit,directory_inherit" var


composer require "jms/serializer-bundle" "@stable"
composer require "friendsofsymfony/rest-bundle" "@stable"
composer require "friendsofsymfony/http-cache-bundle" "~1.0"

# PLAN
- create all end points (hash probably, not id yet)
- go with the DB as it used to be before (think while doing it how to improve it, like user table)
- add unit tests (and e2e from command?)
- improve REST and DB structure, authorisation

# DB
- game (id, user1_id, user2_id, user1_ships, user2_ships, timestamp)
- user table (id, name, email)
- shot table (id, game_id, user_id, coords, result, timestamp)
- chat table (id, game_id, user_id, text, timestamp)
- event/log table (id, game_id, user_id, type, value, timestamp)

# VARNISH INSTALLATION:
## for instance websockets support
https://github.com/mattiasgeniar/varnish-4.0-configuration-templates/blob/master/default.vcl

