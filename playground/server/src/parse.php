<?php

require_once(__DIR__ . "./../../../Utilities.php");
require_once(__DIR__ . "/../../../lexer.php");
require_once(__DIR__ . "/../../../parser.php");
require_once(__DIR__ . "/../../../Token.php");

use PhpParser\Parser;
use PhpParser\Utilities;

$parser = new Parser(file_get_contents($argv[1]));
$sourceFile = $parser->parseSourceFile();

file_put_contents($argv[1] . ".ast", json_encode($sourceFile, JSON_PRETTY_PRINT));

//echo $argv[1];

echo json_encode(iterator_to_array(Utilities::getDiagnostics($sourceFile)), JSON_PRETTY_PRINT);