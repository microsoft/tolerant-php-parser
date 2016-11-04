<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class CaseStatementNode extends Node {
    /** @var Token */
    public $caseKeyword;
    /** @var Expression */
    public $expression;
    /** @var Token */
    public $defaultLabelTerminator;
    /**@var StatementNode[] */
    public $statementList;

    public function __construct() {
        parent::__construct(NodeKind::CaseStatementNode);
    }
}