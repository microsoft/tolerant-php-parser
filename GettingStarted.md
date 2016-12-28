# Getting Started

## Set up your PHP Environment (PHP 7.1 and Composer)
Any version of PHP 7 should work, but as we are still in our early
stages, the "golden path" for now will be PHP 7.1

### Windows
1. Download [PHP 7.1](http://windows.php.net/download#php-7.1)
2. Download and install [Composer](https://getcomposer.org/download/)

Ensure your version of PHP is properly configured by running `php -v` in the command prompt.
If it's not PHP 7.1, then you have a few different options for how to access it:
1. reference it directly: `c:\PHP7.1\php.exe -v`
2. Prepend the directory to your system `PATH`
3. create an alias using `doskey`: `doskey php7="C:\PHP7.1\php.exe" $*`

### Mac
1. Install [Homebrew](http://brew.sh/)
2. Install PHP 7.1 and Composer
```
brew tap homebrew/php
brew install php71
brew install composer
```

Ensure your version of PHP is properly configured by running `php -v`. 
If it's not PHP7, then you have a few different options for how to access it:
1. reference it directly: `/usr/local/Cellar/php71/7.1.0_11/bin/php -v`
2. alias it in your `~/.bash_profile`: `alias php7=/usr/local/Cellar/php71/7.1.0_11/bin/php`
3. add it to your PATH in `~/.bash_profile`: `export PATH="/usr/local/sbin:$PATH"`

## Reference `tolerant-php-parser` from your PHP project
The parser is not yet available on packagist, so you'll instead 
have to specify the location to the github repository.

In your project's `composer.json`, specify the `minimum-stability`, 
`repositories`, and `require` attributes as follows:
```json
{
    "minimum-stability": "dev",
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/mousetraps/tolerant-php-parser.git"
        }
    ],
    "require": {
        "mousetraps/tolerant-php-parser": "master"
    }
}
```

Once you've referenced the parser from your project, run `composer install`,
and be on your way!

## Ready, set, parse!

```php
<?php
$parser = new \PhpParser\Parser();
$fileContents = file_get_contents($myFilename);
$ast = $parser->parseSourceFile($fileContents); # returns an AST representing source file
$errors =  \PhpParser\Utilities::getDiagnostics($ast); # get errors from AST Node
```

## Play around with the AST!
In order to help you get a sense for the features and shape of the tree, 
we've also included a `Parser Playground Extension` that makes use of the parser
to provide error tooltips. 
1. Download the VSIX
2. Point it to your PHP Path
3. Disable other extensions in the workspace to ensure minimal interference

If you see something that looks off, please file an issue, or better yet, contribute as a test case. 

# Contributing
## Building and Running 
1. Fork and clone the repository.
2. `composer-install`
3. `vendor\bin\phpunit` from the root project directory to run the tests to run all the test suites defined in `phpunit.xml`. 
To run individual suites, run `vendor\bin\phpunit --testsuite <test suite name>`.
Note that the validation test suite requires you to also include relevant submodules: `git submodule update --init --recursive`

## Debugging
For debugging, we recommend you install and configure Felix Becker's [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug). For debugging the tests in this project,
you can run the `Listen for XDebug` launch configuration included in this project, and then from the command line:
```
php -debug -d zend_extension=<my-path-to-extension> -d xdebug.remote_enable=1 -d xdebug.remote_autostart=1 vendor\phpunit\phpunit\phpunit <my-phpunit-arguments>
```

Additionally some of the tests output files for failed tests to make it easier to debug.
See the notes in `phpunit.xml` for more info about this behavior.

## Running code coverage
After enabling `xdebug`, run code coverage by executing:
```
php -d memory_limit=500M vendor/bin/phpunit --coverage-html tmp/ tests/
```