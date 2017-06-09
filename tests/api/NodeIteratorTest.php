<?php

use PHPUnit\Framework\TestCase;
use Microsoft\PhpParser\{Parser, NodeIterator, Node};

class NodeIteratorTest extends TestCase {
    
        const FILE_CONTENTS = <<<'PHP'
<?php
function foo($x) {
    if ($x) {
        var_dump($x);
    }
}
foo();
PHP;

    /** @var Node\SourceFileNode */
    private $sourceFile;

    public function setUp() {
        $parser = new Parser();
        $this->sourceFile = $parser->parseSourceFile(self::FILE_CONTENTS);
    }

    public function testIteratesChildren() {
        $iterator = new NodeIterator($this->sourceFile);
        $this->assertEquals(
            [
                $this->sourceFile->statementList[0],
                $this->sourceFile->statementList[1],
                $this->sourceFile->statementList[2],
                $this->sourceFile->endOfFileToken
            ],
            iterator_to_array($iterator, false)
        );
    }

    public function testIteratesDescendants() {
        $iterator = new \RecursiveIteratorIterator(new NodeIterator($this->sourceFile), \RecursiveIteratorIterator::SELF_FIRST);
        $arr = iterator_to_array($iterator, false);
        $this->assertEquals(
            [
                $this->sourceFile->statementList[0],
                $this->sourceFile->statementList[1],
                $this->sourceFile->statementList[1]->functionKeyword,
                $this->sourceFile->statementList[1]->name,
                $this->sourceFile->statementList[1]->openParen,
                $this->sourceFile->statementList[1]->parameters,
                $this->sourceFile->statementList[1]->parameters->children[0],
                $this->sourceFile->statementList[1]->closeParen,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->openBrace,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0],
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->ifKeyword,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->openParen,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->expression,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->closeParen,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->openBrace,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0],
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->callableExpression,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->callableExpression->nameParts[0],
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->openParen,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->argumentExpressionList,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->argumentExpressionList->children[0],
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->argumentExpressionList->children[0]->expression,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->argumentExpressionList->children[0]->expression->name,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->expression->closeParen,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->statements[0]->semicolon,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->statements[0]->statements->closeBrace,
                $this->sourceFile->statementList[1]->compoundStatementOrSemicolon->closeBrace,
                $this->sourceFile->statementList[2]->expression->callableExpression->nameParts[0],
                $this->sourceFile->statementList[2]->expression->openParen,
                $this->sourceFile->statementList[2]->expression->closeParen,
                $this->sourceFile->statementList[2]->semicolon,
                $this->sourceFile->endOfFileToken
            ],
            $arr
        );
    }
}
