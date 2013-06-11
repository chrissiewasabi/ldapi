#!/bin/bash

CWD=`dirname $0`/..
PWD=`pwd`

mkdir -p $HOME/bin

curl -sS https://getcomposer.org/installer | php -- --install-dir=$HOME/bin
php $HOME/bin/composer.phar --working-dir=$CWD update
php $HOME/bin/composer.phar --working-dir=$CWD update
# sudo $HOME/bin//app/reset-permissions -y $USER $GROUP www-data www-data
sudo $HOME/bin//app/reset-permissions

cd -
