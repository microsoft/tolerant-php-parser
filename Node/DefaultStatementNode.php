<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DefaultStatementNode extends Node {
    /** @var Token */
    public $defaultKeyword;
    /** @var Token */
    public $defaultLabelTerminator;
    /**@var StatementNode[] */
    public $statementList;

    public function __construct() {
        parent::__construct(NodeKind::DefaultStatementNode);
    }
}