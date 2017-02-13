<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\DiagnosticsProvider;
use Microsoft\PhpParser\PositionUtilities;

$GLOBALS["PARSER_PATH"] = isset($argv[2]) ? $argv[2] . "/" : __DIR__ . "/../../../src/";

require_once($GLOBALS["PARSER_PATH"] . "bootstrap.php");

$contents = file_get_contents($argv[1]);
$parser = new Parser();
$sourceFile = $parser->parseSourceFile($contents);

file_put_contents($argv[1] . ".ast", json_encode($sourceFile, JSON_PRETTY_PRINT));

$diagnostics = DiagnosticsProvider::getDiagnostics($sourceFile);
$diagnosticsAsLineCol = [];
foreach ($diagnostics as $diagnostic) {
    $diagnosticsAsLineCol[] = [
        "error" => $diagnostic->kind,
        "message" => $diagnostic->message,
        "range" => PositionUtilities::getRangeFromPosition($diagnostic->start, $diagnostic->length, $contents)
    ];
}

echo json_encode($diagnosticsAsLineCol, JSON_PRETTY_PRINT);
