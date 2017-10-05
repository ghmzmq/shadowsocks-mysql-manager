# shadowsocks-mysql-manager
A manager for shadowsocks using mysql(like shadowsocks’s manyuser branch(You can use shadowsocks-libev now

## Progress 30%
## Please edit the config in config.php first

## The traffic module is done
## The support for v2ray is in progress

# Requirements:
* 1.php5.6+(zts)
* 2.pthread
* 3.shadowsocks-python

# Steps to install php-zts & pthreads
* 0. yum install gcc libxml2 libxml2-devel
* 1. wget php.tar.gz(From php.net)
* 2. tar zxvf php.tar.gz && cd php
* 3. ./configure --prefix=/usr/local/php-zts --enable-sockets --enable-pcntl --enable-maintainer-zts --enable-sysvmsg --enable-mbstring --with-mysql --with-mysqli --with-mysql-sock --with-pdo-mysql
* 4. make && make install
* 5. cp php.ini-development /usr/local/php-zts/lib/php.ini
* 5. wget pthreads
* 6. tar zxvf pthreads
* 7. cd pthreads
* 8. /usr/local/php-zts/bin/phpize
* 9. ./configure --with-php-config=/usr/local/php-zts/bin/php-config
* 10. make && make install
* 11. edit /usr/local/php-zts/lib/php.ini and add "extension=pthreads.so" to it
* 12. run /usr/local/php-zts/bin/php -m to check if there has pthreads
* 13. Done!
## Or you can also:
* sudo sh installphp.sh

# Usage:
* 1. edit config.php
* 2. php server.php