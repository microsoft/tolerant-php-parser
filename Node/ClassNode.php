<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ClassNode extends Node {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::ClassNode);
    }
}