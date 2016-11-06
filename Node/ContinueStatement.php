<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ContinueStatement extends StatementNode {
    /** @var Token */
    public $continue;
    /** @var Token | null */
    public $breakoutLevel;
    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::ContinueStatementNode);
    }
}