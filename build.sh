#!/usr/bin/env bash

FILE_PATH=$(cd "$(dirname "$0")"; pwd)

yarn install

export PATH=$FILE_PATH/node_modules/.bin/:$PATH

rm -rf dbbook

php -f ./index.php

cd dbbook

gitbook install

gitbook serve