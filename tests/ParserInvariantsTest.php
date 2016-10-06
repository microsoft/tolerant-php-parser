<?php
// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;
use PhpParser\Node;

class ParserInvariantsTest extends LexerInvariantsTest {
    // TODO test w/ multiple files
    const FILENAMES = array (
        __dir__ . "/cases/parserPocFile.php"
    );

    private $parser;

    public function setUp() {
        $this->parser = new \PhpParser\Parser();

        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            $tokensArray = array();
            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof \PhpParser\Token) {
                    array_push($tokensArray, $child);
                }
            }
            $this->fileToTokensArrayMap[$filename] = $tokensArray;
        }
    }

    public function testSourceFileNodeLengthEqualsDocumentLength()
    {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);
            $this->assertEquals(
                filesize($filename), $sourceFileNode->getLength(),
                "Invariant: The tree length exactly matches the file length.");
        }
    }

    public function testNodesAllHaveAtLeastOneChild() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            foreach ($sourceFileNode->getAllChildren() as $child) {
                if ($child instanceof Node) {
                    $this->assertGreaterThanOrEqual(
                        1, count($child->children),
                        "Invariant: All Nodes have at least one child."
                    );
                }
            }
        }
    }

    public function testEveryNodeSpanIsSumOfChildSpans() {
        foreach (self::FILENAMES as $filename) {
            $sourceFileNode = $this->parser->parseSourceFile($filename);

            $treeElements = array($sourceFileNode);
            array_push($treeElements, $sourceFileNode->getAllChildren());

            foreach ($treeElements as $element) {
                if ($element instanceof Node) {
                    $expectedLength = 0;
                    foreach ($element->children as $child) {
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
    }
}