<?php

use PHPUnit\Framework\TestCase;
use Microsoft\PhpParser\{Parser, NodeIterator, Node};

class NodeIteratorTest extends TestCase {
    
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

    public function testIteratesChildren() {
        $it = new NodeIterator($this->sourceFile);
        $it->rewind();

        // Node\Statement\InlineHtml
        $this->assertTrue($it->valid());
        $this->assertSame('statementList', $it->key());
        $this->assertSame($this->sourceFile->statementList[0], $it->current());
        $it->next();

        // Node\Statement\FunctionDeclaration
        $this->assertTrue($it->valid());
        $this->assertSame('statementList', $it->key());
        $this->assertSame($this->sourceFile->statementList[1], $it->current());
        $it->next();

        // Token(kind=EndOfFileToken)
        $this->assertTrue($it->valid());
        $this->assertSame('endOfFileToken', $it->key());
        $this->assertSame($this->sourceFile->endOfFileToken, $it->current());
        $it->next();

        $this->assertFalse($it->valid());
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
        
        // <?php
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

        // function
        // Token(kind=FunctionKeyword)
        $this->assertTrue($it->valid());
        $this->assertSame('functionKeyword', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->functionKeyword, $it->current());
        $it->next();

        // a
        // Token(kind=Name)
        $this->assertTrue($it->valid());
        $this->assertSame('name', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->name, $it->current());
        $it->next();

        // (
        // Token(kind=OpenParenToken)
        $this->assertTrue($it->valid());
        $this->assertSame('openParen', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->openParen, $it->current());
        $it->next();

        // )
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

        // Node\Statement\CompoundStatementNode
        $this->assertTrue($it->valid());
        $this->assertSame('openBrace', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->openBrace, $it->current());
        $it->next();

        // Node\Statement\ExpressionStatement
        $this->assertTrue($it->valid());
        $this->assertSame('statements', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0], $it->current());
        $it->next();

        // Node\Expression\AssignmentExpression
        $this->assertTrue($it->valid());
        $this->assertSame('expression', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression, $it->current());
        $it->next();

        // Node\Expression\Variable
        $this->assertTrue($it->valid());
        $this->assertSame('leftOperand', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->leftOperand, $it->current());
        $it->next();

        // $a
        // Token(kind=VariableName)
        $this->assertTrue($it->valid());
        $this->assertSame('name', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->leftOperand->name, $it->current());
        $it->next();

        // =
        // Token(kind=EqualsToken)
        $this->assertTrue($it->valid());
        $this->assertSame('operator', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->operator, $it->current());
        $it->next();

        // Node\NumericLiteral
        $this->assertTrue($it->valid());
        $this->assertSame('rightOperand', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->rightOperand, $it->current());
        $it->next();

        // 1
        // Token(kind=IntegerLiteralToken)
        $this->assertTrue($it->valid());
        $this->assertSame('children', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression->rightOperand->children, $it->current());
        $it->next();

        // ;
        // Token(kind=SemicolonToken)
        $this->assertTrue($it->valid());
        $this->assertSame('semicolon', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->semicolon, $it->current());
        $it->next();

        // }
        // Token(kind=CloseBraceToken)
        $this->assertTrue($it->valid());
        $this->assertSame('closeBrace', $it->key());
        $this->assertSame($this->sourceFile->statementList[1]->compoundStatementOrSemicolon->closeBrace, $it->current());
        $it->next();

        // Token(kind=EndOfFileToken)
        $this->assertTrue($it->valid());
        $this->assertSame('endOfFileToken', $it->key());
        $this->assertSame($this->sourceFile->endOfFileToken, $it->current());
        $it->next();

        $this->assertFalse($it->valid());
    }
}
