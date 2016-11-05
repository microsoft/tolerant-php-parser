<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ForeachKey extends Node {
    /** @var Expression */
    public $expression;
    /** @var Token */
    public $arrow;

    public function __construct() {
        parent::__construct(NodeKind::ForeachKeyNode);
    }
}