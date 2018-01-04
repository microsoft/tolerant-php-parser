<?php
// Autoload required classes
require dirname(__DIR__) . "/vendor/autoload.php";

use Microsoft\PhpParser\{DiagnosticsProvider, Node, Parser, PositionUtilities};


const ITERATIONS = 100;
// Return and print an AST from string contents
$main = function() {
    // Instantiate new parser instance
    // TODO: Multiple source files to be realistic.
    $parser = new Parser();
    $t0 = microtime(true);
    $astNode = $parser->parseSourceFile(file_get_contents(__DIR__ . '/../src/Parser.php'));
    $t1 = microtime(true);
    for ($i = 0; $i < ITERATIONS; $i++) {
        $diagnostics = DiagnosticsProvider::getDiagnostics($astNode);
    }
    $t2 = microtime(true);
    printf("Average time to generate diagnostics: %.6f\n", ($t2 - $t1) / ITERATIONS);
    printf("time to parse: %.6f\n", ($t1 - $t0));
    global $__counts;
    var_export($__counts);
};
$main();
