<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class RelativeSpecifier extends Node {
    /** @var Token */
    public $namespaceKeyword;

    /** @var Token */
    public $backslash;

    public function __construct() {
        parent::__construct(NodeKind::RelativeSpecifier);
    }
}