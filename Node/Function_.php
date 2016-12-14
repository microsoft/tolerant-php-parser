<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Function_ extends Node {
    use FunctionHeader, FunctionReturnType, FunctionBody;

    public function __construct() {
        parent::__construct(NodeKind::FunctionNode);
    }
}