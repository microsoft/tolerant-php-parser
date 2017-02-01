<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

// TODO autoload classes
require_once(__DIR__ . "/../src/TokenStreamProviderFactory.php");
require_once(__DIR__ . "/../src/Parser.php");
require_once(__DIR__ . "/../src/Token.php");

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use PHPUnit\Framework\TestCase;
use Microsoft\PhpParser\TokenKind;

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
        $parser = new \Microsoft\PhpParser\Parser();
        self::$sourceFileNode = $parser->parseSourceFile(self::FILE_CONTENTS);
        parent::setUpBeforeClass();
    }

    public function testSourceFileNodePosition() {
        $node = self::$sourceFileNode;
        $this->assertInstanceOf(\Microsoft\PhpParser\Node\Statement\FunctionDeclaration::class, $node->getDescendantNodeAtPosition(15));
        $this->assertInstanceOf(\Microsoft\PhpParser\Node\Expression\Variable::class, $node->getDescendantNodeAtPosition(28));
    }

    public function testRootNodeIsScript() {
        $treeElements = iterator_to_array(self::$sourceFileNode->getDescendantNodes(), false);
        $treeElements[] = self::$sourceFileNode;

        foreach($treeElements as $element) {
            $this->assertInstanceOf(SourceFileNode::class, $element->getRoot());
        }
    }

    public function testFileContentsRetrievableFromAnyNode() {
        $treeElements = iterator_to_array(self::$sourceFileNode->getDescendantNodes(), false);
        $treeElements[] = self::$sourceFileNode;

        foreach($treeElements as $element) {
            $this->assertEquals(self::FILE_CONTENTS, $element->getFileContents());
        }
    }

    public function testFullTextOfRootNodeEqualsFullDocument() {
        $this->assertEquals(self::FILE_CONTENTS, self::$sourceFileNode->getFullText());
    }

    public function testGetTriviaForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \Microsoft\PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualTrivia = $iterator->current()->getLeadingCommentAndWhitespaceText();
        $this->assertEquals('/* contents */ ', $actualTrivia);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getLeadingCommentAndWhitespaceText());
    }

    public function testGetTextForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \Microsoft\PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualText = $iterator->current()->getText();
        $this->assertEquals('$a = 1', $actualText);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getText());
    }

    public function testGetFullTextForNode() {
        $contents = '<?php /* contents */ $a = 1';
        $parser = new \Microsoft\PhpParser\Parser();
        $iterator = $parser->parseSourceFile($contents)->getChildNodes();
        $iterator->next();
        $actualText = $iterator->current()->getFullText();
        $this->assertEquals('/* contents */ $a = 1', $actualText);

        $sourceFile = $parser->parseSourceFile('');
        $this->assertEquals('', $sourceFile->getFullText());
    }
}