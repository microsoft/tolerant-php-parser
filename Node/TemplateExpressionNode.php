<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TemplateExpressionNode extends Node {
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::TemplateExpression);
    }
}