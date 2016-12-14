<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class StaticVariableDeclaration extends Node {

    /** @var Token */
    public $variableName;

    /** @var Token | null */
    public $equalsToken;

    /** @var Expression | null */
    public $assignment;

    public function __construct() {
        parent::__construct(NodeKind::StaticVariableDeclaration);
    }
}