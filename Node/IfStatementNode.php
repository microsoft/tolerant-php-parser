<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class IfStatementNode extends StatementNode {
    /** @var Token */
    public $ifKeyword;
    /** @var Token */
    public $openParen;
    /** @var Expression */
    public $expression;
    /**@var Token */
    public $closeParen;
    /**@var StatementNode */
    public $statement;
    /** @var ElseIfClauseNode[] */
    public $elseIfClauses;
    /**@var ElseClauseNode */
    public $elseClause;

    public function __construct() {
        parent::__construct(NodeKind::IfStatementNode);
    }
}