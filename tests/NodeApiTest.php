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
use PhpParser\Node\Script;
use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class NodeApiTest extends TestCase {
    const FILENAME_PATTERN = __dir__ . "/cases/{parser,}/*.php";

    const FILE_CONTENTS = <<<'PHP'
<?php
function a () {
    // trivia
    $a = 3;
}
PHP;

    static $sourceFileNode;

    public static function setUpBeforeClass() {
        $parser = new \PhpParser\Parser();
        self::$sourceFileNode = $parser->parseSourceFile(self::FILE_CONTENTS);
        parent::setUpBeforeClass();
    }

    public function testSourceFileNodePosition() {
        $node = self::$sourceFileNode;
        $this->assertEquals(\PhpParser\NodeKind::FunctionNode, $node->getNodeAtPosition(15)->kind);
        $this->assertEquals(\PhpParser\NodeKind::Variable, $node->getNodeAtPosition(28)->kind);
    }

    public function testRootNodeIsScript() {
        $treeElements = iterator_to_array(self::$sourceFileNode->getDescendantNodes());
        array_push($treeElements, self::$sourceFileNode);

        foreach($treeElements as $element) {
            $this->assertInstanceOf(Script::class, $element->getRoot());
        }
    }

    public function testFileContentsRetrievableFromAnyNode() {
        $treeElements = iterator_to_array(self::$sourceFileNode->getDescendantNodes());
        array_push($treeElements, self::$sourceFileNode);

        foreach($treeElements as $element) {
            $this->assertEquals(self::FILE_CONTENTS, $element->getFileContents());
        }
    }

    public function testFullTextOfRootNodeEqualsFullDocument() {
        $this->assertEquals(self::FILE_CONTENTS, self::$sourceFileNode->getFullTextForNode());
    }

    public function testGetTriviaForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualTrivia = $iterator->current()->getTriviaForNode();
        $this->assertEquals('/* contents */ ', $actualTrivia);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getTriviaForNode());
    }

    public function testGetTextForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualText = $iterator->current()->getTextForNode();
        $this->assertEquals('$a = 1', $actualText);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getTextForNode());
    }

    public function testGetFullTextForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualText = $iterator->current()->getFullTextForNode();
        $this->assertEquals('/* contents */ $a = 1', $actualText);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getFullTextForNode());
    }
}