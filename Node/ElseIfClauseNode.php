<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ElseIfClauseNode extends StatementNode {
    /** @var Token[] */
    public $elseIfKeyword;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /**@var StatementNode */
    public $statement;

    public function __construct() {
        parent::__construct(NodeKind::ElseIfClauseNode);
    }
}