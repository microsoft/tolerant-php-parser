<?php

$configFile = __DIR__ . "/config.php";
if (file_exists($configFile)) {
    require_once($configFile);
}

if (!isset($GLOBALS["PARSER_PATH"])) {
    $GLOBALS["PARSER_PATH"] = __DIR__ . "/../../../src/";
}

require_once($GLOBALS["PARSER_PATH"] . "bootstrap.php");

use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Utilities;

$contents = file_get_contents($argv[1]);
$parser = new Parser();
$sourceFile = $parser->parseSourceFile($contents);

file_put_contents($argv[1] . ".ast", json_encode($sourceFile, JSON_PRETTY_PRINT));

$diagnostics = Utilities::getDiagnostics($sourceFile);
$diagnosticsAsLineCol = [];
foreach ($diagnostics as $diagnostic) {
    $diagnosticsAsLineCol[] = [
        "error" => $diagnostic->kind,
        "message" => $diagnostic->message,
        "range" => Utilities::getRangeFromPosition($diagnostic->start, $diagnostic->length, $contents)
    ];
}

//echo $argv[1];

echo json_encode($diagnosticsAsLineCol, JSON_PRETTY_PRINT);