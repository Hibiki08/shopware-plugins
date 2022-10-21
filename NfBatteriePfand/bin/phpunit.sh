#!/usr/bin/env bash
DIR="/var/www/html/custom/plugins/NfBatteriePfand/integration.phpunit.xml"
./vendor/bin/phpunit --configuration="$DIR" "$@"