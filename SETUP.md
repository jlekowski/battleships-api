## Server settings
#### Install composer (http://symfony.com/doc/current/cookbook/composer.html)
```
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Install APCu (for compatibility with APC in PHP >= 5.5)
```
sudo apt-get install php5-apcu
```

#### Install Varnish (optional)
```
sudo apt-get install varnish
```

## MySQL settings
#### Create a dabatase (set privileges for a user afterwards if you need to)
```
CREATE DATABASE battleships CHARACTER SET = utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Apache settings
#### If using Apache add in VHOST to catch Authorization headers
            RewriteCond %{HTTP:Authorization} ^(.*)
            RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]

## PHP settings (php.ini, e.g. /etc/php5/apache2/php.ini)
#### Setup OPCache
        opcache.enable=1
        opcache.enable_cli=1
        opcache.memory_consumption=128
        opcache.interned_strings_buffer=8
        opcache.max_accelerated_files=4000
        opcache.validate_timestamps=1
        opcache.revalidate_freq=60
        opcache.revalidate_path=1
        opcache.save_comments=1
        opcache.fast_shutdown=1
        opcache.enable_file_override=1

#### Optionally (no difference for me, but potentially may improve performance)
        set realpath_cache_size = 4096k
        realpath_cache_ttl = 7200

#### OPCache exclusions (if needed)
        /etc/php5/opcache-blacklist.txt # e.g. /var/www/devzone/*
        opcache.blacklist_filename=/etc/php5/opcache-blacklist.txt

## Varnish Settings (version 4, assumption to use Varnish on port 80 and web server on 8080)
#### Change web server port to 8080 (127.0.0.1:8080), change listen and all enabled/available sites
```
# for Apache
sudo vim /etc/apache2/ports.conf
grep 80 /etc/apache2/sites-enabled/*
## for Nginx:
grep 80 /etc/nginx/sites-enabled/*
```

#### Setup varnish cache rules
```
sudo vim /etc/varnish/default.vcl
```
        #
        # This is an example VCL file for Varnish.
        #
        # It does not do anything by default, delegating control to the
        # builtin VCL. The builtin VCL is called when there is no explicit
        # return statement.
        #
        # See the VCL chapters in the Users Guide at https://www.varnish-cache.org/docs/
        # and http://varnish-cache.org/trac/wiki/VCLExamples for more examples.
        
        # Marker to tell the VCL compiler that this VCL has been adapted to the
        # new 4.0 format.
        vcl 4.0;
        
        # Default backend definition. Set this to point to your content server.
        backend default {
            .host = "127.0.0.1";
            .port = "8080";
        }
        
        sub vcl_recv {
            # Happens before we check if we have this in cache already.
            #
            # Typically you clean up the request here, removing cookies you don't need,
            # rewriting the request, etc.
        
            if (req.method == "PURGE") {
                if (!client.ip ~ invalidators) {
                    return (synth(405, "Not allowed"));
                }
                return (purge);
            }
        
            if (req.method == "BAN") {
                if (!client.ip ~ invalidators) {
                    return (synth(405, "Not allowed"));
                }
        
                if (req.http.X-Cache-Tags) {
                    ban("obj.http.X-Host ~ " + req.http.X-Host
                        + " && obj.http.X-Url ~ " + req.http.X-Url
                        + " && obj.http.content-type ~ " + req.http.X-Content-Type
                        + " && obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags
                    );
                } else {
                    ban("obj.http.X-Host ~ " + req.http.X-Host
                        + " && obj.http.X-Url ~ " + req.http.X-Url
                        + " && obj.http.content-type ~ " + req.http.X-Content-Type
                    );
                }
        
                return (synth(200, "Banned"));
            }
        
            # I don't use Cookies
            if (req.http.Cookie) {
                unset req.http.Cookie;
            }
        
            if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
                set req.hash_always_miss = true;
            }
        
            # by default OPTIONS requests are not supported and I want return header for OPTIONS requests
            if (req.method == "OPTIONS") {
                #return (synth(204, "No Content"));
                return (pass);
            }
        
            if (req.http.Authorization && req.method == "GET") {
                # I want to cache requests with Authorization Header
                return (hash);
            }
        }
        
        sub vcl_synth {
            # It responds to all 204 synth (all OPTIONS request), not only the valid ones :/
            if (resp.status == 204) {
                set resp.http.Access-Control-Allow-Origin = "*";
                set resp.http.Access-Control-Allow-Methods = "GET, POST, PUT, PATCH, DELETE, OPTIONS";
                set resp.http.Access-Control-Allow-Headers = "Content-Type, Authorization, Accept, X-Requested-With";
                set resp.http.Access-Control-Expose-Headers = "Location, Api-Key";
            }
        
            return (deliver);
        }
        
        sub vcl_hash {
            hash_data(req.url);
            if (req.http.host) {
                hash_data(req.http.host);
            } else {
                hash_data(server.ip);
            }
        
            # Cache based on Authorization Header
            if (req.http.Authorization) {
                hash_data(req.http.Authorization);
            }
        
            return (lookup);
        }
        
        sub vcl_backend_response {
            # Happens after we have read the response headers from the backend.
            #
            # Here you clean the response headers, removing silly Set-Cookie headers
            # and other mistakes your backend does.
        
            # Set ban-lurker friendly custom headers
            set beresp.http.X-Url = bereq.url;
            set beresp.http.X-Host = bereq.http.host;
        }
        
        sub vcl_deliver {
            # Happens when we have all the pieces we need, and are about to send the
            # response to the client.
            #
            # You can do accounting or modifying the final object here.
        
            # Keep ban-lurker headers only if debugging is enabled
            if (!resp.http.X-Cache-Debug) {
                # Remove ban-lurker friendly custom headers when delivering to client
                unset resp.http.X-Url;
                unset resp.http.X-Host;
                unset resp.http.X-Cache-Tags;
            }
        
            # Add extra headers if debugging is enabled
            # In Varnish 4 the obj.hits counter behaviour has changed, so we use a
            # different method: if X-Varnish contains only 1 id, we have a miss, if it
            # contains more (and therefore a space), we have a hit.
            if (resp.http.X-Cache-Debug) {
                if (resp.http.X-Varnish ~ " ") {
                    set resp.http.X-Cache = "HIT";
                } else {
                    set resp.http.X-Cache = "MISS";
                }
            }
        }
        
        acl invalidators {
            "127.0.0.1";
            # Add any other IP addresses that your application runs on and that you
            # want to allow invalidation requests from. For instance:
            #"192.168.1.0"/24;
        }

#### Setup varnish configuration to listen on port 80
```
sudo vim /etc/default/varnish
```
        DAEMON_OPTS="-a :80 \
                     -T localhost:6082 \
                     -f /etc/varnish/default.vcl \
                     -S /etc/varnish/secret \
                     -s malloc,256m"

```
sudo netstat -tulpn
```

#### Update iptables rules (if needed)
```
sudo vim /etc/iptables/rules.v4
```
        -A INPUT -p tcp -m tcp --dport 8080 -s 127.0.0.1 -j ACCEPT
        -A INPUT -p tcp -m tcp --dport 6082 -s 127.0.0.1 -j ACCEPT
```
sudo service iptables-persistent restart
```

#### If varnish keeps listening on 6081
```
sudo vim /lib/systemd/system/varnish.service
sudo ln -s /lib/systemd/system/varnish.service /etc/systemd/system/varnish.service
systemctl reload varnish.service
systemctl daemon-reload

sudo netstat -tulpn
```

#### handy commands:
```
varnishncsa -F '%U%q (%m) %{Varnish:hitmiss}x' -n ubuntu # see varnish hits
sudo varnishadm "ban req.url ~ /" # ban/clear cache
```

## PHP7 installation (optional)
```
#sudo apt-get install python-software-properties
sudo add-apt-repository ppa:ondrej/php-7.0
sudo apt-get update
sudo apt-get purge php5-fpm -y
sudo apt-get install php7.0 php7.0-fpm php7.0-mysql php7.0-curl php-apcu php-apcu-bc -y
sudo apt-get --purge autoremove -y

php -v
php -m | grep apc
```

#### Setup OPCache in new files
```
sudo vim /etc/php/7.0/fpm/php.ini
sudo vim /etc/php/7.0/cli/php.ini
```

#### For Nginx with PHP-FPM replace socket with /var/run/php/php7.0-fpm.sock
```
grep php5 /etc/nginx/sites-enabled/*

sudo service nginx restart
sudo service php7.0-fpm restart
```
