<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

// TODO autoload classes
require_once(__DIR__ . "/../src/TokenStreamProviderFactory.php");
require_once(__DIR__ . "/../src/Parser.php");
require_once(__DIR__ . "/../src/Token.php");
require_once(__DIR__ . "/LexerInvariantsTest.php");

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class ParserInvariantsTest extends LexerInvariantsTest {
    const FILENAME_PATTERN = __dir__ . "/cases/{parser,}/*.php";

    public static function sourceFileNodeProvider() {
        $testFiles = array();
        $testCases = glob(self::FILENAME_PATTERN, GLOB_BRACE);

        foreach ($testCases as $filename) {
            $parser = new \PhpParser\Parser();
            $testFiles[basename($filename)] = [$filename, $parser->parseSourceFile(file_get_contents($filename))];
        }
        return $testFiles;
    }

    public static function tokensArrayProvider() {
        $testFiles = array();
        $testCases = glob(self::FILENAME_PATTERN, GLOB_BRACE);

        foreach ($testCases as $filename) {
            $parser = new \PhpParser\Parser();
            $sourceFileNode = $parser->parseSourceFile(file_get_contents($filename));
            $tokensArray = array();
            foreach ($sourceFileNode->getDescendantNodesAndTokens() as $child) {
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
    public function testSourceFileNodeLengthEqualsDocumentLength($filename, Node $sourceFileNode) {
        $this->assertEquals(
            filesize($filename), $sourceFileNode->getLength(),
            "Invariant: The tree length exactly matches the file length.");
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testNodesAllHaveAtLeastOneChild($filename, Node $sourceFileNode) {

        foreach ($sourceFileNode->getDescendantNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                $encode = json_encode($child);
                $this->assertGreaterThanOrEqual(
                    1, count($child->getChildNodesAndTokens()),
                    "Invariant: All Nodes have at least one child. $encode"
                );
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEveryNodeSpanIsSumOfChildSpans($filename, Node $sourceFileNode) {
        $treeElements = iterator_to_array($sourceFileNode->getDescendantNodesAndTokens());
        array_push($treeElements, $sourceFileNode);

        foreach ($treeElements as $element) {
            if ($element instanceof Node) {
                $expectedLength = 0;
                foreach ($element->getChildNodesAndTokens() as $child) {
                    if ($child instanceof Node) {
                        $expectedLength += $child->getLength();
                    } elseif ($child instanceof \PhpParser\Token) {
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
    public function testParentOfNodeHasSameChildNode($filename, Node $sourceFileNode) {
        foreach ($sourceFileNode->getDescendantNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                $this->assertContains(
                    $child, $child->parent->getChildNodesAndTokens(),
                    "Invariant: Parent of Node contains same child node."
                );
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEachChildHasExactlyOneParent($filename, Node $sourceFileNode) {
        $treeElements = iterator_to_array($sourceFileNode->getDescendantNodesAndTokens());
        array_push($treeElements, $sourceFileNode);

        foreach ($sourceFileNode->getDescendantNodesAndTokens() as $child) {
            $count = 0;
            foreach ($treeElements as $element) {
                if ($element instanceof Node) {
                    if (in_array($child, iterator_to_array($element->getChildNodesAndTokens()), true)) {
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
    public function testRootNodeHasNoParent($filename, Node $sourceFileNode) {
        $this->assertEquals(
            null, $sourceFileNode->parent,
            "Invariant: Root node of tree has no parent.");
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testRootNodeIsNeverAChild($filename, Node $sourceFileNode) {
        $treeElements = iterator_to_array($sourceFileNode->getDescendantNodesAndTokens());
        array_push($treeElements, $sourceFileNode);

        foreach($treeElements as $element) {
            if ($element instanceof Node) {
                $this->assertNotContains(
                    $sourceFileNode, $element->getChildNodesAndTokens(),
                    "Invariant: root node of tree is never a child.");
            }
        }
    }

    /**
     * @dataProvider sourceFileNodeProvider
     */
    public function testEveryNodeHasAKind($filename, Node $sourceFileNode) {
        $treeElements = iterator_to_array($sourceFileNode->getDescendantNodes());
        array_push($treeElements, $sourceFileNode);

        foreach($treeElements as $element) {
            $this->assertNotNull(
                $element->kind,
                "Invariant: Every Node has a Kind");
        }
    }
}