<?php

require_once(__DIR__ . "./../../../Utilities.php");
require_once(__DIR__ . "/../../../lexer.php");
require_once(__DIR__ . "/../../../parser.php");
require_once(__DIR__ . "/../../../Token.php");

use PhpParser\Parser;
use PhpParser\Utilities;

$contents = file_get_contents($argv[1]);
$parser = new Parser();
$sourceFile = $parser->parseSourceFile($contents);

file_put_contents($argv[1] . ".ast", json_encode($sourceFile, JSON_PRETTY_PRINT));

$diagnostics = Utilities::getDiagnostics($sourceFile);
$diagnosticsAsLineCol = [];
foreach ($diagnostics as $diagnostic) {
    array_push($diagnosticsAsLineCol, [
        "error" => $diagnostic->kind,
        "message" => $diagnostic->message,
        "range" => Utilities::getRangeFromPosition($diagnostic->start, $diagnostic->length, $contents)
    ]);
}

//echo $argv[1];

echo json_encode($diagnosticsAsLineCol, JSON_PRETTY_PRINT);