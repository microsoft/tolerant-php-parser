<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Parser;

require_once __DIR__ . "/../src/bootstrap.php";

$files = [
    __DIR__ . "/../src/Node.php",
    __DIR__ . "/../src/Token.php",
    __DIR__ . "/../src/Parser.php",
    __DIR__ . "/../src/DiagnosticsProvider.php",
    __DIR__ . "/../src/PositionUtilities.php",
    __DIR__ . "/../src/LineCharacterPosition.php",
    __DIR__ . "/../src/MissingToken.php",
    __DIR__ . "/../src/SkippedToken.php"
];

$parser = new Parser();

echo "# API Documentation" . PHP_EOL;
echo "> Note: This documentation was auto-generated using this parser to help dogfood the API. It may be incomplete. Please contribute fixes to
`tools/PrintApiDocumentation.php` and suggest API improvements.\n<hr>\n\n";

foreach ($files as $file) {
    $ast = $parser->parseSourceFile(file_get_contents($file));

    foreach ($ast->getDescendantNodes() as $descendant) {
        if ($descendant instanceof ClassDeclaration) {
            $className = $descendant->name->getText($descendant->getFileContents());
            echo "## " . $className . PHP_EOL;

            // TODO consider not having a separate classMemberDeclarations node
            foreach ($descendant->classMembers->classMemberDeclarations as $member) {
                // TODO: Maybe ask a class directly for all its method declarations
                if ($member instanceof MethodDeclaration) {
                    if (!$member->isPublic()) {
                        continue;
                    }

                    $signature = $member->getSignatureFormatted();

                    $description = $member->getDescriptionFormatted();
                    if (strlen($description) <= 0) {
                        $description = "> TODO: add doc comment\n";
                    }

                    echo "### " . $className . "::" . $member->getName() . PHP_EOL;
                    echo $description . PHP_EOL;
                    echo "```php\n$signature\n```" . PHP_EOL;
                }
            }
        }
    }
}

echo "## Node types
> TODO: complete documentation - in addition to the helper methods on the Node base class,
every Node object has properties specific to the Node type. Browse `src/Node/` to explore these properties.";
