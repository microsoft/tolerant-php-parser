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
            "url": "https://github.com/Microsoft/tolerant-php-parser.git"
        }
    ],
    "require": {
        "Microsoft/tolerant-php-parser": "master"
    }
}
```

Once you've referenced the parser from your project, run `composer install --prefer-dist`,
and be on your way!
> Note: The `--prefer-dist` flag tells Composer to download the minimal set of files,
rather the complete source, which includes tests as well.

## Ready, set, parse!

```php
<?php
require "vendor/autoload.php"; # autoloads required classes

$parser = new PhpParser\Parser(); # instantiates a new parser instance
$astNode = $parser->parseSourceFile('<?php /* comment */ echo "hi!";'); # returns an AST from string contents
$errors =  PhpParser\Utilities::getDiagnostics($astNode); # get errors from AST Node (as a Generator)

var_dump($astNode); # prints full AST
var_dump(iterator_to_array($errors)); # prints all errors

$childNodes = $astNode->getChildNodes();
foreach ($childNodes as $childNode) {
    var_dump([
        "kind" => $childNode->getNodeKindName(), 
        "fullText" => $childNode->getFullTextForNode(),
        "text" => $childNode->getTextForNode(),
        "trivia" => $childNode->getTriviaForNode()
    ]);
}

// For instance, for the expression-statement, the following is returned:
//   array(4) {
//     ["kind"]=>
//     string(19) "ExpressionStatement"
//     ["fullText"]=>
//     string(24) "/* comment */ echo "hi!";"
//     ["text"]=>
//     string(11) "echo "hi!";"
//     ["trivia"]=>
//     string(13) "/* comment */ "
//   }
```

## Play around with the AST!
In order to help you get a sense for the features and shape of the tree, 
we've also included a `PHP Syntax Visualizer Extension` that makes use of the parser
to provide error tooltips. 
1. Download the VSIX
2. Point it to your PHP Path
3. Disable other extensions in the workspace to ensure minimal interference

If you see something that looks off, please file an issue, or better yet, contribute as a test case. See [Contributing.md](Contributing.md) for more details.