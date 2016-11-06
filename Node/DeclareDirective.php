<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DeclareDirective extends Node {
    /** @var Token */
    public $name;
    /** @var Token */
    public $equals;
    /** @var Token */
    public $literal;

    public function __construct() {
        parent::__construct(NodeKind::DeclareDirectiveNode);
    }
}