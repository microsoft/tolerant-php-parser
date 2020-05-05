# Getting Started

## Set up your PHP Environment (PHP 7.2 and Composer)
Any version of PHP 7 should work, but as we are still in our early
stages, the "golden path" for now will be PHP 7.2

### Windows
1. Download [PHP 7.2](http://windows.php.net/download#php-7.2)
2. Download and install [Composer](https://getcomposer.org/download/)

Ensure your version of PHP is properly configured by running `php -v` in the command prompt.
If it's not PHP 7.2, then you have a few different options for how to access it:
1. reference it directly: `c:\PHP7.2\php.exe -v`
2. Prepend the directory to your system `PATH`
3. create an alias using `doskey`: `doskey php7="C:\PHP7.2\php.exe" $*`

### Mac
1. Install [Homebrew](http://brew.sh/)
2. Install PHP 7.2 and Composer
```
brew install php@7.2
brew install composer
```

Ensure your version of PHP is properly configured by running `php -v`.
If it's not PHP7, then you have a few different options for how to access it:
1. reference it directly: `/usr/local/Cellar/php@7.2/7.2.30/bin/php -v`
2. alias it in your `~/.bash_profile`: `alias php7=/usr/local/Cellar/php@7.2/7.2.30/bin/php`
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
            "url": "https://github.com/microsoft/tolerant-php-parser.git"
        }
    ],
    "require": {
        "microsoft/tolerant-php-parser": "master"
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
// Autoload required classes
require __DIR__ . "/vendor/autoload.php";

use Microsoft\PhpParser\{DiagnosticsProvider, Parser};

$parser = new Parser(); # instantiates a new parser instance
$astNode = $parser->parseSourceFile('<?php /* comment */ echo "hi!";'); # returns an AST from string contents
$errors =  DiagnosticsProvider::getDiagnostics($astNode); # get errors from AST Node (as a Generator)

var_dump($astNode); # prints full AST
var_dump(iterator_to_array($errors)); # prints all errors

$childNodes = $astNode->getChildNodes();
foreach ($childNodes as $childNode) {
    var_dump([
        "kind" => $childNode->getNodeKindName(), 
        "fullText" => $childNode->getFullText(),
        "text" => $childNode->getText(),
        "trivia" => $childNode->getLeadingCommentAndWhitespaceText()
    ]);
}

// For instance, for the expression-statement, the following is returned:
//   array(4) {
//     ["kind"]=>
//     string(19) "ExpressionStatement"
//     ["fullText"]=>
//     string(25) "/* comment */ echo "hi!";"
//     ["text"]=>
//     string(11) "echo "hi!";"
//     ["trivia"]=>
//     string(14) "/* comment */ "
//   }
```

> Note: the API is not yet finalized, so please file issues let us know what functionality you want exposed, 
and we'll see what we can do! Also please file any bugs with unexpected behavior in the parse tree. We're still
in our early stages, and any feedback you have is much appreciated :smiley:.

## Play around with the AST!
In order to help you get a sense for the features and shape of the tree, 
we've also included a [Syntax Visualizer Tool](../syntax-visualizer/client#php-parser-syntax-visualizer-tool)
that makes use of the parser to both visualize the tree and provide error tooltips.
![image](https://cloud.githubusercontent.com/assets/762848/21635753/3f8c0cb8-d214-11e6-8424-e200d63abc18.png)

![image](https://cloud.githubusercontent.com/assets/762848/21705272/d5f2f7d8-d373-11e6-9688-46ead75b2fd3.png)

If you see something that looks off, please file an issue, or better yet, contribute as a test case. See [Contributing.md](../Contributing.md) for more details.

## Next Steps
Check out the [Syntax Overview](Overview.md) section for more information on key attributes of the parse tree, 
or the [How It Works](HowItWorks.md) section if you want to dive deeper into the implementation.
