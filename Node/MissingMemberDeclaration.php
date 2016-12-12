<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class MissingMemberDeclaration extends Node {

    /** @var Token[] */
    public $modifiers;

    public function __construct() {
        parent::__construct(NodeKind::MissingMemberDeclaration);
    }
}