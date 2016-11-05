<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DoStatement extends StatementNode {
    /** @var Token */
    public $do;
    /** @var StatementNode */
    public $statement;
    /** @var Token */
    public $whileToken;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /**@var Token | null */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::DoWhileStatementNode);
    }
}