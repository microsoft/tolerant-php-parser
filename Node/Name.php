<?php

namespace PhpParser\Node;


use PhpParser\NodeKind;

class Name extends Node {
    public function __construct() {
        parent::__construct(NodeKind::Name);
    }
}