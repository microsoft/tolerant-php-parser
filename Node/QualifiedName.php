<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class QualifiedName extends Node {
    /** @var Token */
    public $globalSpecifier; // \_opt
    /** @var Node */
    public $relativeSpecifier; // namespace\

    /** @var DelimitedList */
    public $nameParts;

    public function __construct() {
        parent::__construct(NodeKind::QualifiedName);
    }
}