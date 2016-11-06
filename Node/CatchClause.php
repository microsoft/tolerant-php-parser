<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class CatchClause extends Node {
    /** @var Token */
    public $catch;
    /** @var Token */
    public $openParen;
    /** @var QualifiedName */
    public $qualifiedName;
    /** @var Token */
    public $variableName;
    /**@var Token */
    public $closeParen;
    /**@var StatementNode */
    public $compoundStatement;

    public function __construct() {
        parent::__construct(NodeKind::CatchClauseNode);
    }
}