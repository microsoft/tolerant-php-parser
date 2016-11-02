<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class StatementNode extends Node {
    public function __construct(int $kind) {
        parent::__construct($kind);
    }
}