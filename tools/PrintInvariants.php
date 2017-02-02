<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Parser;

require_once __DIR__ . "/../src/bootstrap.php";

$lexerInvariants = __DIR__ . "/../tests/LexerInvariantsTest.php";
$parserInvariants = __DIR__ . "/../tests/ParserInvariantsTest.php";

$parser = new Parser();
$lexerInvariantsAst = $parser->parseSourceFile(file_get_contents($lexerInvariants));
$parserInvariantsAst = $parser->parseSourceFile(file_get_contents($parserInvariants));

echo "# Invariants" . PHP_EOL;
echo "> This documentation was auto-generated using this parser to help dogfood the API. Please contribute
 fixes to `tools/PrintInvariants.php` and suggest API improvements.\n\n";

echo "We define and test both parser and lexer against a set of invariants (characteristics 
about the produced token set or tree that always hold true, no matter what the input). This set of invariants provides 
a consistent foundation that makes it easier to ensure the tree is \"structurally sound\", and confidently 
reason about the tree as we continue to build up our understanding.\n\n";


echo "## Token Invariants" . PHP_EOL;
printInvariants($lexerInvariantsAst);

echo PHP_EOL;
echo "## Node Invariants" . PHP_EOL;
printInvariants($parserInvariantsAst);

function printInvariants(Node $ast) {
    foreach ($ast->getDescendantNodes() as $descendant) {
        if ($descendant instanceof StringLiteral) {
            $stringContents = $descendant->getStringContentsText();
            if (($index = strpos(strtolower($stringContents), $invariantText = "invariant: ")) !== false) {
                echo "- " . substr($stringContents, $index + strlen($invariantText)) . PHP_EOL;
            }
        }
        if ($descendant instanceof Node\Statement\ClassDeclaration) {
            $baseClass = $descendant->classBaseClause->baseClass->getText();
            if (strpos($baseClass, "LexerInvariants") !== false) {
                echo "- " . "All invariants of Tokens" . PHP_EOL;
            }
        }
    }
}
