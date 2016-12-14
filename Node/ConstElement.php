<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ConstElement extends Node {

    /** @var Token */
    public $name;

    /** @var Token */
    public $equalsToken;

    /** @var Expression */
    public $assignment;

    public function __construct() {
        parent::__construct(NodeKind::ConstElement);
    }
}