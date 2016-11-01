<?php

namespace PhpParser\Node;

use PhpParser\NodeKind;

class Script extends Node {
    public $scriptSectionList;

    public function __construct() {
        parent::__construct(NodeKind::SourceFileNode);
    }
}