<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class FinallyClause extends Node {
    /** @var Token */
    public $finallyToken;
    /**@var StatementNode */
    public $compoundStatement;

    public function __construct() {
        parent::__construct(NodeKind::FinallyClauseNode);
    }
}