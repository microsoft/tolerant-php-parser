<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ElseClauseNode extends StatementNode {
    /** @var Token */
    public $elseKeyword;
    /**@var StatementNode */
    public $statement;

    public function __construct() {
        parent::__construct(NodeKind::ElseClauseNode);
    }
}