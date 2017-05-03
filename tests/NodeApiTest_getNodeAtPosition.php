<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Microsoft\PhpParser\Node\Statement\IfStatementNode;
use Microsoft\PhpParser\Node\Statement\NamespaceDefinition;
use Microsoft\PhpParser\Parser;
use PHPUnit\Framework\TestCase;

class GetNodeAtPositionTest extends TestCase {

    public function testSourceFileNodePosition() {
        $leadingTrivia = <<<'PHP'
<?php
function a() {
    // _trivia
    $a = 3;
}
PHP;
        $this->assertNodeAtPositionInstanceOf(
            $leadingTrivia,
            \Microsoft\PhpParser\Node\Statement\CompoundStatementNode::class,
            "Finding position at leading trivia does not return corresponding Node"
        );

        $name = <<<'PHP'
<?php
function a() {
    // trivia
    _$a = 3;
}
PHP;
        $this->assertNodeAtPositionInstanceOf(
            $name,
            \Microsoft\PhpParser\Node\Expression\Variable::class
        );

        $name = <<<'PHP'
<?php
function _a() {
    // trivia
    $a = 3;
}
PHP;
        $this->assertNodeAtPositionInstanceOf(
            $name,
            \Microsoft\PhpParser\Node\Statement\FunctionDeclaration::class
        );

        $text = <<<'PHP'
<?php
function a() {
    $a_
    $b = 2;
}
PHP;
        $this->assertNodeAtPositionText(
            $text,
            '$a'
        );

        $text = <<<'PHP'
<?php
function a() {
    $a->f_oo();
}
PHP;
        $this->assertNodeAtPositionText(
            $text,
            '$a->foo'
        );

        $text = <<<'PHP'
<?php
function a() {
    $a->foo_
    $b = 1;
}
PHP;
        $this->assertNodeAtPositionText(
            $text,
            '$a->foo'
        );

        $text = <<<'PHP'
<?php
function a() {
    $a->_$b;
}
PHP;
        $this->assertNodeAtPositionText(
            $text,
            '$b'
        );

        $text = <<<'PHP'
<?php
function a() {
    $a->_;$b;
}
PHP;
        $this->assertNodeAtPositionText(
            $text,
            '$a->'
        );
    }

    private function getNodeAtPosition($contents): Node {
        $parser = new Parser();
        $pos = strpos($contents, '_');
        $contents = str_replace('_', '', $contents);

        $node = $parser->parseSourceFile($contents);

        $actualNode = $node->getDescendantNodeAtPosition($pos);
        $this->assertNotNull($actualNode);

        return $actualNode;
    }

    /**
     * @param string $contents
     * @param $expectedClass
     */
    private function assertNodeAtPositionInstanceOf($contents, $expectedClass, $message = '') {
        $actualNode = $this->getNodeAtPosition($contents);
        $text = $actualNode->getText();
        $message = "Got node with text: $text" . ($message ? PHP_EOL . $message : '');
        $this->assertInstanceOf($expectedClass, $actualNode, $message);
    }

    /**
     * @param string $contents
     * @param $expectedClass
     */
    private function assertNodeAtPositionText($contents, $expectedText, $message = '') {
        $actualNode = $this->getNodeAtPosition($contents);
        $text = $actualNode->getText();
        $message = "Got node with text: $text" . ($message ? PHP_EOL . $message : '');
        $this->assertEquals($expectedText, $text, $message);
    }
}
