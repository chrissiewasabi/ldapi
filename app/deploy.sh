#!/bin/bash

CWD=`dirname $0`/..
PWD=`pwd`

curl -sS https://getcomposer.org/installer | php
php ./composer.phar --workinf-dir $CWD update
php ./composer.phar --workinf-dir $CWD update
# sudo ./app/reset-permissions -y $USER $GROUP www-data www-data
sudo ./app/reset-permissions

cd -
