<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ThrowStatement extends StatementNode {
    /** @var Token */
    public $throwKeyword;
    /** @var Expression */
    public $expression;
    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::ThrowStatement);
    }
}