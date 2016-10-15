<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class MethodBlockNode extends Node {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::MethodBlockNode);
    }
}