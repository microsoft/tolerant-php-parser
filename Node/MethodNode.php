<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class MethodNode extends FunctionDefinition {
    /** @var Token[] */
    public $modifiers;

    public function __construct() {
        parent::__construct(NodeKind::MethodNode);
    }
}