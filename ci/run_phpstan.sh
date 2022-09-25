#!/usr/bin/env bash
# -x Exit immediately if any command fails
# -e Echo all commands being executed.
# -u Fail for undefined variables
set -xeu
# This installs and runs phpstan
# TODO: Remove separate phpstan install step after https://github.com/microsoft/tolerant-php-parser/pull/385 is merged
if [ ! -d vendor/phpstan/phpstan ]; then
    composer.phar require --dev phpstan/phpstan=^1.8
fi
./vendor/bin/phpstan analyze
