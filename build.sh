#!/usr/bin/env bash

rm -rf dbbook && cnpm install gitbook-cli -g && php -f ./index.php && cd dbbook && gitbook install && gitbook build && cp -r _book/* /home/tech/www/dbbook