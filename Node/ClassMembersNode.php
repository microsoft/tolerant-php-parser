<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ClassMembersNode extends Node {
    /** @var Token */
    public $openBrace;

    /** @var Token | null */
    public $classMemberDeclarations;

    /** @var Token */
    public $closeBrace;

    public function __construct() {
        parent::__construct(NodeKind::ClassMembersNode);
    }
}