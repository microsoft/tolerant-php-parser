<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Parameter extends Node {
    /** @var Token */
    public $typeOpt;
    /** @var Token */
    public $byRefToken;

    /** @var Token */
    public $variableName;

    /** @var Token */
    public $equalsToken;

    /** @var null | Expression */
    public $default;


    public function __construct() {
        parent::__construct(NodeKind::ParameterNode);
    }

    public function isVariadic() {

    }
}