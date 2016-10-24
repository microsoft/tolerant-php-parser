<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Function_ extends FunctionDefinition {
    public function __construct() {
        parent::__construct(NodeKind::FunctionNode);
    }
}