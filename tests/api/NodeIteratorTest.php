<?php

use PHPUnit\Framework\TestCase;
use Microsoft\PhpParser\{Parser, NodeIterator, Node};

class NodeIteratorTest extends TestCase {
    
    const FILE_CONTENTS = '
        <?php
        function a() {
            $a = 1;
            $b = 2;
        }
        a();
    ';

    /** @var Node\SourceFileNode */
    private $sourceFile;

    public function setUp() {
        $parser = new Parser();
        $this->sourceFile = $parser->parseSourceFile(self::FILE_CONTENTS);
    }

    public function testIteratesChildren() {
        $iterator = new NodeIterator($this->sourceFile);
        $iterator->rewind();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->sourceFile->statementList[0], $iterator->current());
        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->sourceFile->statementList[1], $iterator->current());
        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->sourceFile->statementList[2], $iterator->current());
        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame($this->sourceFile->endOfFileToken, $iterator->current());
        $iterator->next();

        $this->assertFalse($iterator->valid());
    }

    public function testRecursiveIteratorIteratorIteratesDescendants() {
        
        $it = new \RecursiveIteratorIterator(new NodeIterator($this->sourceFile), \RecursiveIteratorIterator::SELF_FIRST);
        $it->rewind();
        
        // Node\Statement\InlineHtml 
        $this->assertTrue($it->valid());
        $this->assertSame('statementList', $it->key());
        $this->assertSame($this->sourceFile->statementList[0], $it->current());
        $it->next();
        
        // Token(kind=InlineHtml)
        $this->assertTrue($it->valid());
        $this->assertSame('text', $it->key());
        $this->assertSame($this->sourceFile->statementList[0]->text, $it->current());
        $it->next();
        
        // Token(kind=ScriptSectionStartTag)
        $this->assertTrue($it->valid());
        $this->assertSame('scriptSectionStartTag', $it->key());
        $this->assertSame($this->sourceFile->statementList[0]->scriptSectionStartTag, $it->current());
        $it->next();

        // Node\Statement\FunctionDeclaration
        $this->assertTrue($it->valid());
        $this->assertSame('statementList', $it->key());
        $this->assertSame($this->sourceFile->statementList[1], $it->current());
        $it->next();

        // Token(kind=FunctionKeyword)
        $this->assertTrue($it->valid());
        $this->assertSame('functionKeyword', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->functionKeyword, $it->current());
        $it->next();

        // Token(kind=Name)
        $this->assertTrue($it->valid());
        $this->assertSame('name', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->name, $it->current());
        $it->next();

        // Token(kind=OpenParenToken)
        $this->assertTrue($it->valid());
        $this->assertSame('openParen', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->openParen, $it->current());
        $it->next();

        // Token(kind=CloseParenToken)
        $this->assertTrue($it->valid());
        $this->assertSame('closeParen', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->closeParen, $it->current());
        $it->next();

        // Node\Statement\CompoundStatementNode
        $this->assertTrue($it->valid());
        $this->assertSame('compoundStatementOrSemicolon', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon, $it->current());
        $it->next();

        // TODO finish
        $this->markTestIncomplete();
    }
}
