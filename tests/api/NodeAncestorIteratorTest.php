<?php

use PHPUnit\Framework\TestCase;
use Microsoft\PhpParser\{Parser, Node};
use Microsoft\PhpParser\Iterator\NodeAncestorIterator;

class NodeAncestorIteratorTest extends TestCase {
    
    const FILE_CONTENTS = '
        <?php
        function a() {
            $a = 1;
        }
    ';

    /** @var Node\SourceFileNode */
    private $sourceFile;

    public function setUp() {
        $parser = new Parser();
        $this->sourceFile = $parser->parseSourceFile(self::FILE_CONTENTS);
    }

    public function testIteratesAncestors() {
        $it = new NodeAncestorIterator($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->leftOperand);
        $it->rewind();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->leftOperand, $it->current());
        $it->next();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression, $it->current());
        $it->next();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0], $it->current());
        $it->next();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon, $it->current());
        $it->next();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile->statementList[1], $it->current());
        $it->next();

        $this->assertTrue($it->valid());
        $this->assertSame($this->sourceFile, $it->current());
        $it->next();

        $this->assertFalse($it->valid());
    }
}
