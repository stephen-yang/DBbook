#!/usr/bin/env bash

rm -rf dbbook
cnpm install gitbook-cli -g
php -f ./index.php
cd dbbook
gitbook install
gitbook build
scp -r _book/* stephen@dadaabc.dev:/home/tech/www/dbbook