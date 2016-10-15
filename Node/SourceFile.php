<?php

namespace PhpParser\Node;

use PhpParser\NodeKind;

class SourceFile extends Node {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::SourceFileNode);
    }
}