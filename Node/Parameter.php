<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Parameter extends Node {
    /** @var QualifiedName | Token | null */
    public $typeDeclaration;
    /** @var Token | null */
    public $byRefToken;
    /** @var Token | null */
    public $dotDotDotToken;

    /** @var Token */
    public $variableName;

    /** @var Token | null */
    public $equalsToken;

    /** @var null | Expression */
    public $default;


    public function __construct() {
        parent::__construct(NodeKind::ParameterNode);
    }

    public function isVariadic() {

    }
}