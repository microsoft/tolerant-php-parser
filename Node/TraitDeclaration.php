<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TraitDeclaration extends Node {

    /** @var Token */
    public $traitKeyword;

    /** @var Name */
    public $name;

    /** @var TraitMembers */
    public $traitMembers;

    public function __construct() {
        parent::__construct(NodeKind::TraitDeclaration);
    }
}