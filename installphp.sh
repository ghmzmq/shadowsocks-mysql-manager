#!/bin/bash
echo "Version 0.1"
cur_dir=$(pwd)
php_version='php-7.0.24'
pthread_version='pthreads-3.1.6'
echo "Files will download to ${cur_dir}/installphp"
echo -e "\033[33m Are you sure to install PHP&Pthreads for SSMysqlManager? \033[0m"
read -p "Enter your choice (Y/n): " Select
if [[ "${Select}" = "y" || "${Select}" = "Y" ]]; then
    echo "Installation Started!"
else
    echo "Canceled!"
    exit 1
fi
rm -rf ${cur_dir}/installphp
mkdir -p ${cur_dir}/installphp
echo "[1/4] Clearing Folders";
cd ${cur_dir}/installphp
rm -rf "${php_version}.tar.gz"
rm -rf "${pthread_version}.tar.gz"
rm -rf "${php_version}"
rm -rf "${pthread_version}"
rm -rf /usr/local/php-zts/
echo "Done!"
echo "[2/4] Downloading PHP&Pthread Files";
wget -q "https://raw.githubusercontent.com/Zzm317/shadowsocks-mysql-manager/master/installphp/${php_version}.tar.gz" 
wget -q "https://raw.githubusercontent.com/Zzm317/shadowsocks-mysql-manager/master/installphp/${pthread_version}.tar.gz" 
echo "Downloaded!"
echo "[3/4] Unzip PHP&Pthread Files";
cd ${cur_dir}/installphp
tar -zxvf ${php_version}.tar.gz
echo "PHP Unziped!"
cd ${cur_dir}/installphp
tar -zxvf ${pthread_version}.tar.gz
echo "PThreads Unziped!"
echo "[4/4] Making PHP&Pthread"
cd ${cur_dir}/installphp/${php_version}
./configure --prefix=/usr/local/php-zts --enable-sockets --enable-pcntl --disable-fileinfo --enable-maintainer-zts --enable-sysvmsg --enable-mbstring --with-mysqli --with-mysql-sock --with-pdo-mysql
make && make install
cp php.ini-development /usr/local/php-zts/lib/php.ini
cd ../
cd ${cur_dir}/installphp/${pthread_version}
/usr/local/php-zts/bin/phpize
./configure --with-php-config=/usr/local/php-zts/bin/php-config
make && make install
echo "extension=pthreads.so" >> /usr/local/php-zts/lib/php.ini
echo "Done!"
/usr/local/php-zts/bin/php -m
echo "Please Check if pthreads is isset below!"
rm -rf ${cur_dir}/installphp