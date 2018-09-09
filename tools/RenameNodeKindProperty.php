<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Parser;

require_once __DIR__ . "/../src/bootstrap.php";

$parser = new Parser();
$asts = [];

$directoryIterator = new RecursiveDirectoryIterator(__DIR__ . "/../src/");
foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
    if (strpos($file, ".php")) {
        $asts[] = $parser->parseSourceFile(file_get_contents($file));
    }
}

$searchReplace = [];

foreach ($asts as $ast) {
    foreach ($ast->getDescendantNodes() as $node) {
        if ($node instanceof MethodDeclaration) {
            $newNodeText = $nodeText = $node->getText();
            if (strpos($node->name->getText($node->getFileContents()), "__construct") !== false) {
                if (($compoundStatement = $node->compoundStatementOrSemicolon) instanceof CompoundStatementNode) {
                    foreach ($compoundStatement->getDescendantNodes() as $descendant) {
                        if ($descendant instanceof ScopedPropertyAccessExpression) {
                            // TODO add method to retrieve arguments or parameters array
//                            if (isset($descendant->argumentExpressionList[0]) && $descendant->argumentExpressionList[0]
                            if (strpos($descendant->scopeResolutionQualifier->getText($descendant->getText()), "NodeKind") !== false) {
                                $classDeclaration = $node->getFirstAncestor(ClassDeclaration::class);
                                $className = $classDeclaration->name->getText($node->getFileContents());
                                $searchReplace[$descendant->memberName->getText($node->getFileContents())] = $className;
                            }
                        }
                    }
                }
            }
        }
    }
}
var_dump($searchReplace);
$directoryIterator = new RecursiveDirectoryIterator(__DIR__ . "/../src/");
foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
    if (strpos($file, ".php")) {
        $newText = $text = file_get_contents($file);
        foreach ($searchReplace as $search=>$replace) {
            if (strpos($replace, $search) !== false) {
                $temp = $search;
                $search = $replace;
                $replace = $temp;
            }
            $newText = str_replace(
                $search,
                $replace,
                $newText);
            if ($newText !== $text) {
                echo $file . PHP_EOL;
                file_put_contents($file, $newText);
            }
        }
    }
}
