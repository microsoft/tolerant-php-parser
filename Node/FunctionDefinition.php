<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class FunctionDefinition extends Node {
    /** @var Token */
    public $functionKeyword;
    /** @var Token */
    public $byRefToken;
    /** @var null | Name */
    public $name;
    /** @var Token */
    public $openParen;
    /** @var DelimitedList[] */
    public $parameters;
    /** @var Token */
    public $closeParen;
    /** @var Token */
    public $colonToken;
    /** @var null | Name */
    public $returnType;
    /** @var null | CompoundStatementNode */
    public $compoundStatement;
}