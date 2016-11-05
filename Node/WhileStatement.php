<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class WhileStatement extends StatementNode {
    /** @var Token */
    public $whileToken;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /** @var Token | null */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;
    /**@var Token | null */
    public $endWhile;
    /**@var Token | null */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::WhileStatementNode);
    }
}