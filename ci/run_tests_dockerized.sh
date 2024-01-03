#!/usr/bin/env bash
if [ $# != 1 ]; then
    echo "Usage: $0 PHP_VERSION" 1>&2
    echo "e.g. $0 8.0" 1>&2
    echo "The PHP_VERSION is the version of the php docker image to use" 1>&2
    echo "This runs the tests that have been set up in GitHub Workflows so far" 1>&2
    exit 1
fi
# -x Exit immediately if any command fails
# -e Echo all commands being executed.
# -u fail for undefined variables
set -xeu
PHP_VERSION=$1

DOCKER_IMAGE="tolerant-php-parser-test-runner:$PHP_VERSION"
docker build --build-arg="PHP_VERSION=$PHP_VERSION" --tag="$DOCKER_IMAGE" -f ci/Dockerfile .
# Run all of the phpunit test suites in CI.
# - Add the validation folder as a read-only volume for running "phpunit --testsuite validation"
#   (This is around 180MB, so it is not added to the docker image)
docker run --volume="$PWD/validation:/tolerant-php-parser/validation:ro" --rm $DOCKER_IMAGE ci/run_tests.sh
