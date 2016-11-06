<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class BreakOrContinueStatement extends StatementNode {
    /** @var Token */
    public $breakOrContinueKeyword;
    /** @var Token | null */
    public $breakoutLevel;
    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::BreakOrContinueStatementNode);
    }
}