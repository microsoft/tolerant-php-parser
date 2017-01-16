<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use PhpParser\Node\MethodDeclaration;
use PhpParser\Node\PropertyDeclaration;
use PhpParser\Node\Statement\ClassDeclaration;
use PhpParser\Parser;
use PhpParser\Token;
use PhpParser\TokenKind;

require_once __DIR__ . "/../src/bootstrap.php";

$files = [
    __DIR__ . "/../src/Node.php",
    __DIR__ . "/../src/Token.php",
    __DIR__ . "/../src/Parser.php"
];

$parser = new Parser();

echo "# API Documentation" . PHP_EOL;
echo "> Note: This documentation was auto-generated using this parser to help dogfood the API. Please contribute fixes to
`tools/PrintApiDocumentation.php` and suggest API improvements.\n<hr>\n\n";

foreach ($files as $file) {
    $ast = $parser->parseSourceFile(file_get_contents($file));
    foreach ($ast->getDescendantNodes() as $descendant) {
        if ($descendant instanceof ClassDeclaration) {
            $className = $descendant->name->getText($descendant->getFileContents());
            echo "## " . $className . PHP_EOL;

            // TODO consider not having a separate classMemberDeclarations node
            foreach ($descendant->classMembers->classMemberDeclarations as $member) {
                if ($member instanceof MethodDeclaration) {
                    // TODO this should be a helper function on any modified types
                    foreach ($member->modifiers as $modifier) {
                        if ($modifier->kind === TokenKind::PublicKeyword) {
                            $fileContents = $member->getFileContents();
                            $signature = implode(" ", getSignatureParts($member));
                            $comment = trim($member->getLeadingCommentAndWhitespaceText(), "\r\n");

                            $commentParts = explode("\n", $comment);
                            $description = [];
                            foreach ($commentParts as $i=>$part) {
                                $part = trim($part, "*\r\t /");
                                if (isset($part[0])) {
                                    if ($part[0] === "@") {
                                        break;
                                    }
                                    $description[] = $part;
                                }
                            }
                            $comment = implode(" ", $description);
                            if (strlen(trim($comment, " \t")) === 0) {
                                $comment = "> TODO: add doc comment\n";
                            }
                            echo "### " . $className . "::" . $member->name->getText($member->getFileContents()) . PHP_EOL;
                            echo $comment . PHP_EOL;
                            echo "```php\n$signature\n```" . PHP_EOL;
                        }
                    }
                }
            }
        }
    }
}

function getSignatureParts(MethodDeclaration $methodDeclaration) : array {
    // TODO - something like this in API?
    $parts = [];
    foreach ($methodDeclaration->getChildNodesAndTokens() as $i=>$child) {
        if ($i === "compoundStatementOrSemicolon") {
            return $parts;
        }
        $parts[] = $child instanceof Token
            ? $child->getText($methodDeclaration->getFileContents())
            : $child->getText();
    };
    return $parts;
}