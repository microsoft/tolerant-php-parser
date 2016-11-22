<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ConstDeclaration extends Node {

    /** @var Token[] */
    public $modifiers;

    /** @var Token */
    public $constKeyword;

    /** @var DelimitedList */
    public $constElements;

    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::ConstDeclaration);
    }
}