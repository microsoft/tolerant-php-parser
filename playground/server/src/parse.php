<?php

require_once(__DIR__ . "/../../../lexer.php");
require_once(__DIR__ . "/../../../parser.php");
require_once(__DIR__ . "/../../../Token.php");

$parser = new \PhpParser\Parser($argv[1]);
$sourceFile = $parser->parseSourceFile();

file_put_contents($argv[1] . ".ast", json_encode($sourceFile, JSON_PRETTY_PRINT));

// echo $argv[1];

echo json_encode( iterator_to_array($parser->getErrors($sourceFile)), JSON_PRETTY_PRINT);
