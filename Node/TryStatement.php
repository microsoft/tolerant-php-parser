<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TryStatement extends StatementNode {
    /** @var Token */
    public $tryKeyword;
    /** @var StatementNode */
    public $compoundStatement;
    /** @var CatchClause[] | null */
    public $catchClauses;
    /**@var FinallyClause | null */
    public $finallyClause;

    public function __construct() {
        parent::__construct(NodeKind::IfStatementNode);
    }
}