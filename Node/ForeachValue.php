<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ForeachValue extends Node {
    /** @var Token | null */
    public $ampersand;
    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::ForeachValueNode);
    }
}