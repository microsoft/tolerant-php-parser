<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ElseIfClauseNode extends Node {
    /** @var Token[] */
    public $elseIfKeyword;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /**@var Token | null */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;

    public function __construct() {
        parent::__construct(NodeKind::ElseIfClauseNode);
    }
}