<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

// TODO autoload classes
require_once(__DIR__ . "/../src/TokenStreamProviderFactory.php");
require_once(__DIR__ . "/../src/Parser.php");
require_once(__DIR__ . "/../src/Token.php");

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class NodeApiTest extends TestCase {
    const FILENAME_PATTERN = __dir__ . "/cases/{parser,}/*.php";

    public function testSourceFileNodePosition() {
        $parser = new \PhpParser\Parser();
        $node = $parser->parseSourceFile(<<<'EOT'
<?php
function a () {
    $a = 3;
}
EOT
        );
        $this->assertEquals(\PhpParser\NodeKind::FunctionNode, $node->getNodeAtPosition(15)->kind);
        $this->assertEquals(\PhpParser\NodeKind::Variable, $node->getNodeAtPosition(28)->kind);
    }
}