<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class PropertyDeclaration extends Node {

    /** @var Token[] */
    public $modifiers;

    /** @var DelimitedList */
    public $propertyElements;

    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::PropertyDeclaration);
    }
}