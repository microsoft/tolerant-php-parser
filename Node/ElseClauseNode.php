<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ElseClauseNode extends Node {
    /** @var Token[] */
    public $elseKeyword;
    /** @var Token */
    public $colon;
    /**@var StatementNode | StatementNode[] */
    public $statements;

    public function __construct() {
        parent::__construct(NodeKind::ElseClauseNode);
    }
}