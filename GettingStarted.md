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
$parser = new \PhpParser\Parser($myFilename);
$ast = $parser->parseSourceFile(); # returns an AST representing source file
$errors =  $parser->getErrors($ast); # get errors from AST Node
```

# Contributing
1. Fork and clone the repository
2. `composer-install`
3. `vendor\phpunit\phpunit tests` from the root project directory to run the tests
