<?php
// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");
require_once(__DIR__ . "/LexerInvariantsTest.php");

use PhpParser\Node\Node;
use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class ParserInvariantsTest extends LexerInvariantsTest {
    const FILENAMES = array (
        __dir__ . "/cases/parserPocFile.php",
        __dir__ . "/cases/parserPocFile2.php"
    );

    public static function sourceFileNodeProvider() {
        $testFiles = array();
        foreach (self::FILENAMES as $filename) {
            $parser = new \PhpParser\Parser($filename);
            $testFiles[basename($filename)] = [$filename, $parser->parseSourceFile()];
        }
        return $testFiles;
    }

    public static function tokensArrayProvider() {
        $testFiles = array();
        foreach (self::FILENAMES as $filename) {
            $parser = new \PhpParser\Parser($filename);
            $sourceFileNode = $parser->parseSourceFile();
            $tokensArray = array();
            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof \PhpParser\Token) {
                    array_push($tokensArray, $child);
                }
            }
            $testFiles[basename($filename)] = [$filename, $tokensArray];
        }
        return $testFiles;
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testSourceFileNodeLengthEqualsDocumentLength($filename, $sourceFileNode) {
        $this->assertEquals(
            filesize($filename), $sourceFileNode->getLength(),
            "Invariant: The tree length exactly matches the file length.");
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testNodesAllHaveAtLeastOneChild($filename, $sourceFileNode) {

        foreach ($sourceFileNode->getAllChildren() as $child) {
            if ($child instanceof Node) {
                $this->assertGreaterThanOrEqual(
                    1, count($child->getChildren()),
                    "Invariant: All Nodes have at least one child."
                );
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEveryNodeSpanIsSumOfChildSpans($filename, $sourceFileNode) {
        $treeElements = $sourceFileNode->getAllChildren();
        array_push($treeElements, $sourceFileNode);

        foreach ($treeElements as $element) {
            if ($element instanceof Node) {
                $expectedLength = 0;
                foreach ($element->getChildren() as $child) {
                    if ($child instanceof Node) {
                        $expectedLength += $child->getLength();
                    } else if ($child instanceof \PhpParser\Token) {
                        $expectedLength += $child->length;
                    }
                }
                $this->assertEquals(
                    $expectedLength, $element->getLength(),
                    "Invariant: Span of any Node is span of child nodes and tokens."
                );
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testParentOfNodeHasSameChildNode($filename, $sourceFileNode) {
        foreach ($sourceFileNode->getAllChildren() as $child) {
            if ($child instanceof Node) {
                $this->assertContains(
                    $child, $child->parent->getChildren(),
                    "Invariant: Parent of Node contains same child node."
                );
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEachChildHasExactlyOneParent($filename, $sourceFileNode) {

        $treeElements = $sourceFileNode->getAllChildren();
        array_push($treeElements, $sourceFileNode);

        foreach ($sourceFileNode->getAllChildren() as $child) {
            $count = 0;
            foreach ($treeElements as $element) {
                if ($element instanceof Node) {
                    if (in_array($child, $element->getChildren(), true)) {
                        $count++;
                    }
                }
            }
            $this->assertEquals(
                1, $count,
                "Invariant: each child has exactly one parent.");
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testRootNodeHasNoParent($filename, $sourceFileNode) {
        $this->assertEquals(
            null, $sourceFileNode->parent,
            "Invariant: Root node of tree has no parent.");
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testRootNodeIsNeverAChild($filename, $sourceFileNode) {
        $treeElements = $sourceFileNode->getAllChildren();
        array_push($treeElements, $sourceFileNode);

        foreach($treeElements as $element) {
            if ($element instanceof Node) {
                $this->assertNotContains(
                    $sourceFileNode, $element->getChildren(),
                    "Invariant: root node of tree is never a child.");
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEveryNodeHasAKind($filename, $sourceFileNode) {
        $treeElements = $sourceFileNode->getAllChildren();
        array_push($treeElements, $sourceFileNode);

        foreach($treeElements as $element) {
            if ($element instanceof Node) {
                $this->assertNotNull(
                    $element->kind,
                    "Invariant: Every Node has a Kind");
            }
        }
    }
}