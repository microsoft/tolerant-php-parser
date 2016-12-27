<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PhpParser\Node\Node;
use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class NodeApiTest extends TestCase {
    const FILENAME_PATTERN = __dir__ . "/cases/{parser,}/*.php";

    public function testSourceFileNodePosition() {
        $parser = new \PhpParser\Parser(<<<'EOT'
<?php
function a () {
    $a = 3;
}
EOT
        );
        $node = $parser->parseSourceFile();
        $this->assertEquals(\PhpParser\NodeKind::FunctionNode, $node->getNodeAtPosition(15)->kind);
        $this->assertEquals(\PhpParser\NodeKind::Variable, $node->getNodeAtPosition(28)->kind);
    }
}