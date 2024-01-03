#!/usr/bin/env bash
if [ $# != 1 ]; then
    echo "Usage: $0 PHP_VERSION" 1>&2
    echo "e.g. $0 8.0" 1>&2
    echo "The PHP_VERSION is the version of the php docker image to use to run phpstan (static analysis)" 1>&2
    exit 1
fi
# -x Exit immediately if any command fails
# -e Echo all commands being executed.
# -u fail for undefined variables
set -xeu
PHP_VERSION=$1

# This is the same image name, Dockerfile and build arguments as ci/run_tests_dockerized.sh
DOCKER_IMAGE="tolerant-php-parser-test-runner:$PHP_VERSION"
docker build --build-arg="PHP_VERSION=$PHP_VERSION" --tag="$DOCKER_IMAGE" -f ci/Dockerfile .
docker run --rm $DOCKER_IMAGE ci/run_phpstan.sh
